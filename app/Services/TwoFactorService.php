<?php

namespace App\Services;

use App\Models\User;

class TwoFactorService
{
    /**
     * Generate a new 2FA secret for the user.
     */
    public function generateSecret(User $user): string
    {
        $secret = bin2hex(random_bytes(16));
        $user->update(['mfa_secret' => $secret]);
        return $secret;
    }

    /**
     * Verify a TOTP code against the user's secret.
     * Placeholder — implement real TOTP verification later.
     */
    public function verify(User $user, string $code): bool
    {
        // Stub: accept any 6-digit code for now
        return strlen($code) === 6;
    }

    /**
     * Disable 2FA for the user.
     */
    public function disable(User $user): void
    {
        $user->update([
            'mfa_enabled' => false,
            'mfa_secret' => null,
        ]);
    }

    /**
     * Send an SMS OTP to the given phone number.
     */
    public function sendSmsOtp(string $phone): string
    {
        $otp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        // TODO: integrate Twilio/WhatsApp later
        return $otp;
    }
}
