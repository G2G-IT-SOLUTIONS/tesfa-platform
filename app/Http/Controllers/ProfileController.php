<?php

namespace App\Http\Controllers;

use App\Services\GDPRComplianceService;
use App\Services\TwoFactorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class ProfileController extends Controller
{
    public function __construct(
        private GDPRComplianceService $gdpr,
        private TwoFactorService $twoFactor,
    ) {}

    /**
     * Show profile page
     */
    public function show()
    {
        $user = auth()->user()->load(['creditScore', 'agentProfile']);

        return view('pages.profile', compact('user'));
    }

    /**
     * Update profile
     */
    public function update(Request $request)
    {
        $user = auth()->user();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20|unique:users,phone,' . $user->id,
            'preferred_currency' => 'nullable|in:ETB,USD,EUR,GBP',
            'preferred_locale' => 'nullable|in:en,am,om,ti',
            'country' => 'nullable|string|max:100',
            'city' => 'nullable|string|max:100',
            'neighborhood' => 'nullable|string|max:100',
            'diaspora_location' => 'nullable|string|max:100',
            'timezone' => 'nullable|string|max:50',
        ]);

        $user->update($validated);

        // Log data processing activity
        $this->gdpr->logProcessing($user, 'data_correction');

        return back()->with('success', 'Profile updated successfully.');
    }

    /**
     * Update password
     */
    public function updatePassword(Request $request)
    {
        $validated = $request->validate([
            'current_password' => 'required|string',
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
        ]);

        $user = auth()->user();

        if (!Hash::check($validated['current_password'], $user->password)) {
            return back()->withErrors(['current_password' => 'Current password is incorrect.']);
        }

        $user->update(['password' => Hash::make($validated['password'])]);

        // Invalidate other sessions
        \DB::table('sessions')
            ->where('user_id', $user->id)
            ->where('id', '!=', session()->getId())
            ->delete();

        return back()->with('success', 'Password updated successfully.');
    }

    /**
     * Settings page
     */
    public function settings()
    {
        $user = auth()->user();

        return view('pages.settings', compact('user'));
    }

    /**
     * Update notification preferences
     */
    public function updateNotifications(Request $request)
    {
        $user = auth()->user();

        $preferences = $request->validate([
            'email_notifications' => 'nullable|boolean',
            'sms_notifications' => 'nullable|boolean',
            'push_notifications' => 'nullable|boolean',
            'marketing_emails' => 'nullable|boolean',
        ]);

        foreach ($preferences as $type => $granted) {
            $this->gdpr->recordConsent($user, $type, (bool) $granted, '1.0');
        }

        return back()->with('success', 'Notification preferences updated.');
    }

    /**
     * Enable 2FA
     */
    public function enable2fa(Request $request)
    {
        $user = auth()->user();

        if ($user->mfa_enabled) {
            return back()->with('info', '2FA is already enabled.');
        }

        $secret = $this->twoFactor->generateSecret();
        $qrUrl = $this->twoFactor->getQrCodeUrl($user, $secret);
        $recoveryCodes = $this->twoFactor->generateRecoveryCodes($user);

        $user->update(['mfa_secret' => $secret]);

        return view('pages.settings-2fa', compact('secret', 'qrUrl', 'recoveryCodes'));
    }

    /**
     * Confirm and activate 2FA
     */
    public function confirm2fa(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|size:6',
        ]);

        $user = auth()->user();

        if (!$this->twoFactor->verify($user->mfa_secret, $validated['code'])) {
            return back()->withErrors(['code' => 'Invalid verification code.']);
        }

        $user->update(['mfa_enabled' => true]);

        return redirect()->route('settings')
            ->with('success', 'Two-factor authentication enabled.');
    }

    /**
     * Disable 2FA
     */
    public function disable2fa(Request $request)
    {
        $validated = $request->validate([
            'password' => 'required|string',
        ]);

        $user = auth()->user();

        if (!Hash::check($validated['password'], $user->password)) {
            return back()->withErrors(['password' => 'Password is incorrect.']);
        }

        $user->update([
            'mfa_enabled' => false,
            'mfa_secret' => null,
            'two_factor_recovery_codes' => null,
        ]);

        return back()->with('success', 'Two-factor authentication disabled.');
    }

    /**
     * Update avatar
     */
    public function updateAvatar(Request $request)
    {
        $validated = $request->validate([
            'avatar' => 'required|image|mimes:jpeg,png,webp|max:5120',
        ]);

        $user = auth()->user();
        $user->clearMediaCollection('avatar');
        $user->addMediaFromRequest('avatar')->toMediaCollection('avatar');

        return back()->with('success', 'Profile photo updated.');
    }
}