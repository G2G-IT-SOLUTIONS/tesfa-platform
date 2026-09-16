<?php
//Route::get('/debug-laravel', function () { return 'Laravel OK'; });

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\{
    HomeController,
    AuthController,
    PropertyController,
    VerificationController,
    EscrowController,
    RentToOwnController,
    ShortTermController,
    AgentController,
    MarketIntelController,
    NotificationController,
    PaymentController,
    DashboardController,
    ProfileController,
    ConsentController
};

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/about', [HomeController::class, 'about'])->name('about');
Route::get('/contact', [HomeController::class, 'contact'])->name('contact');
Route::get('/privacy', [HomeController::class, 'privacy'])->name('privacy');
Route::get('/terms', [HomeController::class, 'terms'])->name('terms');

// Health check
Route::get('/health', function () {
    $checks = [
        'database' => DB::connection()->getPdo() !== null,
        'redis' => Redis::ping() === '+PONG',
        'storage' => is_writable(storage_path()),
        'queue' => \Illuminate\Support\Facades\Queue::size() < 10000,
    ];
    $healthy = !in_array(false, $checks);
    return response()->json([
        'status' => $healthy ? 'healthy' : 'unhealthy',
        'checks' => $checks,
        'timestamp' => now()->toIso8601String(),
    ], $healthy ? 200 : 503);
});

/*
|--------------------------------------------------------------------------
| Authentication Routes
|--------------------------------------------------------------------------
*/

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
    Route::get('/forgot-password', [AuthController::class, 'showForgotPassword'])->name('password.request');
    Route::post('/forgot-password', [AuthController::class, 'sendResetLink'])->name('password.email');
    Route::get('/reset-password/{token}', [AuthController::class, 'showResetPassword'])->name('password.reset');
    Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('password.update');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/verify-otp', [AuthController::class, 'showVerifyOtp'])->name('verify.otp');
    Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);
    Route::post('/resend-otp', [AuthController::class, 'resendOtp'])->name('resend.otp');
});

/*
|--------------------------------------------------------------------------
| Property Routes
|--------------------------------------------------------------------------
*/

Route::prefix('properties')->name('properties.')->group(function () {
    Route::get('/', [PropertyController::class, 'index'])->name('index');
    Route::get('/create', [PropertyController::class, 'create'])->name('create')->middleware('auth');
    Route::post('/', [PropertyController::class, 'store'])->name('store')->middleware('auth');
    Route::get('/{property:slug}', [PropertyController::class, 'show'])->name('show');
    Route::get('/{property}/edit', [PropertyController::class, 'edit'])->name('edit')->middleware('auth');
    Route::put('/{property}', [PropertyController::class, 'update'])->name('update')->middleware('auth');
    Route::delete('/{property}', [PropertyController::class, 'destroy'])->name('destroy')->middleware('auth');
    Route::post('/{property}/inquiry', [PropertyController::class, 'inquiry'])->name('inquiry');
    Route::post('/{property}/save', [PropertyController::class, 'save'])->name('save')->middleware('auth');
    Route::post('/{property}/share', [PropertyController::class, 'share'])->name('share');
});

/*
|--------------------------------------------------------------------------
| Verification Routes
|--------------------------------------------------------------------------
*/

Route::prefix('verifications')->name('verifications.')->middleware('auth')->group(function () {
    Route::get('/', [VerificationController::class, 'index'])->name('index');
    Route::post('/{property}/request', [VerificationController::class, 'request'])->name('request');
    Route::get('/{verification}', [VerificationController::class, 'show'])->name('show');
    Route::post('/{verification}/accept', [VerificationController::class, 'accept'])->name('accept')->middleware('role:agent_certified,agent_premier,agent_master');
    Route::post('/{verification}/complete', [VerificationController::class, 'complete'])->name('complete')->middleware('role:agent_certified,agent_premier,agent_master');
});

/*
|--------------------------------------------------------------------------
| Escrow Routes
|--------------------------------------------------------------------------
*/

Route::prefix('escrow')->name('escrow.')->middleware('auth')->group(function () {
    Route::get('/', [EscrowController::class, 'index'])->name('index');
    Route::get('/create/{property}', [EscrowController::class, 'create'])->name('create');
    Route::post('/', [EscrowController::class, 'store'])->name('store');
    Route::get('/{escrow}', [EscrowController::class, 'show'])->name('show');
    Route::post('/{escrow}/deposit', [EscrowController::class, 'deposit'])->name('deposit');
    Route::post('/{escrow}/submit-documents', [EscrowController::class, 'submitDocuments'])->name('submit-documents');
    Route::post('/{escrow}/confirm', [EscrowController::class, 'confirm'])->name('confirm');
    Route::post('/{escrow}/dispute', [EscrowController::class, 'dispute'])->name('dispute');
    Route::post('/{escrow}/cancel', [EscrowController::class, 'cancel'])->name('cancel');
});

/*
|--------------------------------------------------------------------------
| Rent-to-Own Routes
|--------------------------------------------------------------------------
*/

Route::prefix('rent-to-own')->name('rent-to-own.')->middleware('auth')->group(function () {
    Route::get('/', [RentToOwnController::class, 'index'])->name('index');
    Route::get('/create/{property}', [RentToOwnController::class, 'create'])->name('create');
    Route::post('/', [RentToOwnController::class, 'store'])->name('store');
    Route::get('/{contract}', [RentToOwnController::class, 'show'])->name('show');
    Route::post('/{contract}/payment', [RentToOwnController::class, 'makePayment'])->name('payment');
    Route::get('/{contract}/schedule', [RentToOwnController::class, 'schedule'])->name('schedule');
});

/*
|--------------------------------------------------------------------------
| Short-Term Booking Routes
|--------------------------------------------------------------------------
*/

Route::prefix('bookings')->name('bookings.')->middleware('auth')->group(function () {
    Route::get('/', [ShortTermController::class, 'index'])->name('index');
    Route::get('/create/{property}', [ShortTermController::class, 'create'])->name('create');
    Route::post('/', [ShortTermController::class, 'store'])->name('store');
    Route::get('/{booking}', [ShortTermController::class, 'show'])->name('show');
    Route::post('/{booking}/cancel', [ShortTermController::class, 'cancel'])->name('cancel');
    Route::post('/{booking}/review', [ShortTermController::class, 'review'])->name('review');
});

/*
|--------------------------------------------------------------------------
| Agent Routes
|--------------------------------------------------------------------------
*/

Route::prefix('agent')->name('agent.')->middleware(['auth', 'role:agent_certified,agent_premier,agent_master'])->group(function () {
    Route::get('/dashboard', [AgentController::class, 'dashboard'])->name('dashboard');
    Route::get('/jobs', [AgentController::class, 'jobs'])->name('jobs');
    Route::get('/earnings', [AgentController::class, 'earnings'])->name('earnings');
    Route::get('/profile', [AgentController::class, 'profile'])->name('profile');
    Route::put('/profile', [AgentController::class, 'updateProfile'])->name('profile.update');
    Route::post('/availability', [AgentController::class, 'toggleAvailability'])->name('availability');
});

/*
|--------------------------------------------------------------------------
| Market Intelligence Routes
|--------------------------------------------------------------------------
*/

Route::prefix('market-intelligence')->name('market.')->group(function () {
    Route::get('/', [MarketIntelController::class, 'index'])->name('index');
    Route::get('/neighborhood/{neighborhood}', [MarketIntelController::class, 'neighborhood'])->name('neighborhood');
    Route::get('/trends', [MarketIntelController::class, 'trends'])->name('trends');
});

/*
|--------------------------------------------------------------------------
| Dashboard Routes
|--------------------------------------------------------------------------
*/

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/profile', [ProfileController::class, 'show'])->name('profile');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');
    Route::get('/settings', [ProfileController::class, 'settings'])->name('settings');
    Route::get('/credit-score', [DashboardController::class, 'creditScore'])->name('credit-score');
    Route::get('/my-properties', [PropertyController::class, 'myProperties'])->name('my-properties');
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/{notification}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead'])->name('notifications.read-all');
});

/*
|--------------------------------------------------------------------------
| Payment Routes
|--------------------------------------------------------------------------
*/

Route::prefix('payments')->name('payments.')->middleware('auth')->group(function () {
    Route::get('/', [PaymentController::class, 'index'])->name('index');
    Route::post('/telebirr/initiate', [PaymentController::class, 'initiateTelebirr'])->name('telebirr.initiate');
    Route::get('/telebirr/callback', [PaymentController::class, 'telebirrCallback'])->name('telebirr.callback');
    Route::post('/cbe/initiate', [PaymentController::class, 'initiateCBE'])->name('cbe.initiate');
    Route::get('/cbe/callback', [PaymentController::class, 'cbeCallback'])->name('cbe.callback');
});

// Telebirr webhook (no auth)
Route::post('/webhooks/telebirr', [PaymentController::class, 'telebirrWebhook'])->name('webhooks.telebirr');
Route::post('/webhooks/cbe', [PaymentController::class, 'cbeWebhook'])->name('webhooks.cbe');

/*
|--------------------------------------------------------------------------
| GDPR / Consent Routes
|--------------------------------------------------------------------------
*/

Route::prefix('legal')->name('legal.')->group(function () {
    Route::get('/consent', [ConsentController::class, 'show'])->name('consent');
    Route::post('/consent', [ConsentController::class, 'store'])->name('consent.store');
    // Route::middleware('auth')->group(function () {
    //     Route::post('/consent/withdraw', [ConsentController::class, 'withdraw'])->name('consent.withdraw');
    //     Route::get('/data-request', [ConsentController::class, 'showDataRequest'])->name('data-request');
    //     Route::post('/data-request', [ConsentController::class, 'submitDataRequest'])->name('data-request.submit');
    // });
});

/*
|--------------------------------------------------------------------------
| PWA / Offline
|--------------------------------------------------------------------------
*/

Route::get('/offline', function () {
    return response()->file(public_path('offline.html'));
})->name('offline');