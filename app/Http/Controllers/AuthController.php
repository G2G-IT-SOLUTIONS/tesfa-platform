<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserConsent;
use App\Services\TwoFactorService;
use App\Services\NotificationService;
use App\Events\UserRegistered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;

class AuthController extends Controller
{
    public function __construct(
        private TwoFactorService $twoFactor,
        private NotificationService $notifications,
    ) {}

    /*
    |--------------------------------------------------------------------------
    | Login
    |--------------------------------------------------------------------------
    */

    public function showLogin()
    {
        return view('pages.auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|string',
            'password' => 'required|string',
        ]);

        // Determine if email or phone
        $field = filter_var($credentials['email'], FILTER_VALIDATE_EMAIL) ? 'email' : 'phone';
        $credentials[$field] = $credentials['email'];
        unset($credentials['email']);

        // Rate limiting
        $key = 'login:' . $request->ip() . ':' . ($credentials[$field] ?? 'unknown');
        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);
            throw ValidationException::withMessages([
                'email' => "Too many login attempts. Please try again in {$seconds} seconds.",
            ]);
        }

        $user = User::where($field, $credentials[$field])->first();

        // Check account locked
        if ($user && $user->locked_until && $user->locked_until->isFuture()) {
            throw ValidationException::withMessages([
                'email' => "Account locked. Try again after {$user->locked_until->diffForHumans()}.",
            ]);
        }

        // Check active
        if ($user && !$user->is_active) {
            throw ValidationException::withMessages([
                'email' => 'Your account has been deactivated. Contact support.',
            ]);
        }

        // Attempt login
        if (!Auth::attempt($credentials, $request->boolean('remember'))) {
            RateLimiter::hit($key, 300);

            if ($user) {
                $user->increment('failed_login_attempts');

                // Lock after 5 attempts
                if ($user->failed_login_attempts >= 5) {
                    $user->update(['locked_until' => now()->addMinutes(15)]);
                }
            }

            throw ValidationException::withMessages([
                'email' => 'The provided credentials do not match our records.',
            ]);
        }

        RateLimiter::clear($key);
        $request->session()->regenerate();

        $user->update([
            'last_login_at' => now(),
            'failed_login_attempts' => 0,
            'locked_until' => null,
        ]);

        // If MFA enabled, redirect to challenge
        if ($user->mfa_enabled) {
            session()->put('mfa_required', $user->id);
            Auth::logout();
            return redirect()->route('mfa.challenge');
        }

        // If phone not verified, redirect to OTP
        if (!$user->phone_verified_at) {
            return redirect()->route('verify.otp');
        }

        return redirect()->intended(route('dashboard'));
    }

    /*
    |--------------------------------------------------------------------------
    | Registration
    |--------------------------------------------------------------------------
    */

    public function showRegister()
    {
        return view('pages.auth.register');
    }

    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users',
            'phone' => 'required|string|max:20|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'required|in:buyer,seller',
            'terms' => 'required|accepted',
            'recaptcha_token' => 'nullable|string',
        ]);

        // Verify reCAPTCHA
        if (config('services.recaptcha.secret_key')) {
            $captchaValid = $this->verifyRecaptcha($request->input('recaptcha_token'));
            if (!$captchaValid) {
                throw ValidationException::withMessages([
                    'recaptcha_token' => 'CAPTCHA verification failed. Please try again.',
                ]);
            }
        }

        // Normalize phone
        $phone = $this->normalizePhone($validated['phone']);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $phone,
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
            'country' => 'Ethiopia',
            'city' => 'Addis Ababa',
            'preferred_currency' => 'ETB',
            'is_active' => true,
        ]);

        // Assign role
        $user->assignRole($validated['role']);

        // Record consent
        UserConsent::create([
            'user_id' => $user->id,
            'consent_type' => 'terms_of_service',
            'granted' => true,
            'granted_at' => now(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'consent_version' => '1.0',
        ]);

        UserConsent::create([
            'user_id' => $user->id,
            'consent_type' => 'privacy_policy',
            'granted' => true,
            'granted_at' => now(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'consent_version' => '1.0',
        ]);

        // Send OTP
        $this->twoFactor->sendSmsOtp($user);

        // Fire event
        event(new UserRegistered($user));

        // Send welcome email
        $this->notifications->send($user, 'welcome', [
            'title' => 'Welcome to Tesfa!',
            'message' => "Hi {$user->name}, your account is ready. Verify your phone to get started.",
            'name' => $user->name,
            'action_url' => route('verify.otp'),
        ], ['database', 'mail']);

        Auth::login($user);

        return redirect()->route('verify.otp')
            ->with('success', 'Account created! We sent a verification code to your phone.');
    }

    /*
    |--------------------------------------------------------------------------
    | Logout
    |--------------------------------------------------------------------------
    */

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home')->with('success', 'You have been logged out.');
    }

    /*
    |--------------------------------------------------------------------------
    | OTP Verification
    |--------------------------------------------------------------------------
    */

    public function showVerifyOtp()
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        if (auth()->user()->phone_verified_at) {
            return redirect()->route('dashboard');
        }

        return view('pages.auth.verify-otp');
    }

    public function verifyOtp(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|size:6',
        ]);

        $user = $request->user();

        if ($user->phone_verified_at) {
            return redirect()->route('dashboard');
        }

        if (!$this->twoFactor->verifySmsOtp($user, $validated['code'])) {
            throw ValidationException::withMessages([
                'code' => 'Invalid or expired code. Please try again.',
            ]);
        }

        $user->update([
            'phone_verified_at' => now(),
            'id_verified' => false, // Still need ID verification
        ]);

        // Compute initial credit score
        app(\App\Services\AI\CreditScoringService::class)->computeScore($user);

        return redirect()->route('dashboard')
            ->with('success', 'Phone verified! Welcome to Tesfa.');
    }

    public function resendOtp(Request $request)
    {
        $user = $request->user();

        if ($user->phone_verified_at) {
            return back()->with('info', 'Your phone is already verified.');
        }

        // Rate limit: 1 SMS per 60 seconds
        $key = "resend-otp:{$user->id}";
        if (RateLimiter::tooManyAttempts($key, 1)) {
            $seconds = RateLimiter::availableIn($key);
            return back()->with('error', "Please wait {$seconds} seconds before requesting another code.");
        }

        RateLimiter::hit($key, 60);

        $this->twoFactor->sendSmsOtp($user);

        return back()->with('success', 'A new code has been sent to your phone.');
    }

    /*
    |--------------------------------------------------------------------------
    | Password Reset
    |--------------------------------------------------------------------------
    */

    public function showForgotPassword()
    {
        return view('pages.auth.forgot-password');
    }

    public function sendResetLink(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email|exists:users,email',
        ]);

        // Rate limit
        $key = "password-reset:{$request->ip()}";
        if (RateLimiter::tooManyAttempts($key, 3)) {
            throw ValidationException::withMessages([
                'email' => 'Too many reset attempts. Please try again later.',
            ]);
        }
        RateLimiter::hit($key, 300);

        $status = Password::sendResetLink($validated);

        if ($status === Password::RESET_LINK_SENT) {
            return back()->with('success', 'Password reset link sent! Check your email.');
        }

        throw ValidationException::withMessages([
            'email' => __($status),
        ]);
    }

    public function showResetPassword(Request $request, string $token)
    {
        return view('pages.auth.reset-password', [
            'token' => $token,
            'email' => $request->query('email'),
        ]);
    }

    public function resetPassword(Request $request)
    {
        $validated = $request->validate([
            'token' => 'required|string',
            'email' => 'required|email',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $status = Password::reset(
            $validated,
            function (User $user, string $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();

                // Invalidate all sessions except current
                \DB::table('sessions')->where('user_id', $user->id)->delete();

                // Notify user
                $this->notifications->send($user, 'password_changed', [
                    'title' => 'Password changed',
                    'message' => 'Your Tesfa password was successfully reset. If this wasn\'t you, contact support immediately.',
                ], ['database', 'mail']);
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return redirect()->route('login')
                ->with('success', 'Password reset! You can now sign in.');
        }

        throw ValidationException::withMessages([
            'email' => __($status),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    private function verifyRecaptcha(?string $token): bool
    {
        if (!$token) return false;

        try {
            $response = \Http::asForm()->post('https://www.google.com/recaptcha/api/siteverify', [
                'secret' => config('services.recaptcha.secret_key'),
                'response' => $token,
                'remoteip' => request()->ip(),
            ]);

            $result = $response->json();
            return ($result['success'] ?? false) && ($result['score'] ?? 0) >= 0.5;
        } catch (\Exception $e) {
            \Log::error('reCAPTCHA verification failed', ['error' => $e->getMessage()]);
            return false;
        }
    }

    private function normalizePhone(string $phone): string
    {
        // Remove spaces, dashes
        $phone = preg_replace('/[^0-9+]/', '', $phone);

        // Add +251 if missing
        if (str_starts_with($phone, '0')) {
            $phone = '+251' . substr($phone, 1);
        } elseif (str_starts_with($phone, '9') && strlen($phone) === 9) {
            $phone = '+251' . $phone;
        } elseif (!str_starts_with($phone, '+')) {
            $phone = '+251' . $phone;
        }

        return $phone;
    }
}