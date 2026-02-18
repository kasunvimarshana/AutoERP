<?php

declare(strict_types=1);

namespace Modules\IAM\Services\MFA;

use Modules\IAM\Models\User;
use PragmaRX\Google2FA\Google2FA;
use Illuminate\Support\Str;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

/**
 * Multi-Factor Authentication Service
 * 
 * Handles TOTP-based 2FA using Google Authenticator compatible apps
 */
class MFAService
{
    private Google2FA $google2fa;
    
    public function __construct()
    {
        $this->google2fa = new Google2FA();
    }

    /**
     * Generate a new MFA secret for a user
     */
    public function generateSecret(): string
    {
        return $this->google2fa->generateSecretKey();
    }

    /**
     * Enable MFA for a user
     */
    public function enableMFA(User $user, string $secret): void
    {
        $user->update([
            'mfa_enabled' => true,
            'mfa_secret' => encrypt($secret),
            'mfa_backup_codes' => $this->generateBackupCodes(),
        ]);
    }

    /**
     * Disable MFA for a user
     */
    public function disableMFA(User $user): void
    {
        $user->update([
            'mfa_enabled' => false,
            'mfa_secret' => null,
            'mfa_backup_codes' => null,
        ]);
    }

    /**
     * Verify a TOTP code
     */
    public function verifyCode(User $user, string $code): bool
    {
        if (!$user->mfa_enabled || !$user->mfa_secret) {
            return false;
        }

        $secret = decrypt($user->mfa_secret);
        
        // Check if it's a backup code
        if ($this->verifyBackupCode($user, $code)) {
            return true;
        }

        // Verify TOTP code with 2 window tolerance (±1 minute)
        return $this->google2fa->verifyKey($secret, $code, 2);
    }

    /**
     * Verify a backup code
     */
    private function verifyBackupCode(User $user, string $code): bool
    {
        $backupCodes = $user->mfa_backup_codes ?? [];
        
        foreach ($backupCodes as $index => $backupCode) {
            if (hash_equals($backupCode, $code)) {
                // Remove used backup code
                unset($backupCodes[$index]);
                $user->update(['mfa_backup_codes' => array_values($backupCodes)]);
                return true;
            }
        }
        
        return false;
    }

    /**
     * Generate backup codes
     */
    private function generateBackupCodes(int $count = 10): array
    {
        $codes = [];
        
        for ($i = 0; $i < $count; $i++) {
            $codes[] = $this->generateBackupCode();
        }
        
        return $codes;
    }

    /**
     * Generate a single backup code
     */
    private function generateBackupCode(): string
    {
        return strtoupper(Str::random(4) . '-' . Str::random(4) . '-' . Str::random(4));
    }

    /**
     * Get QR code as SVG for setting up MFA
     */
    public function getQRCode(User $user, string $secret): string
    {
        $qrCodeUrl = $this->google2fa->getQRCodeUrl(
            config('app.name'),
            $user->email,
            $secret
        );

        $renderer = new ImageRenderer(
            new RendererStyle(200),
            new SvgImageBackEnd()
        );
        
        $writer = new Writer($renderer);
        
        return $writer->writeString($qrCodeUrl);
    }

    /**
     * Get remaining backup codes count
     */
    public function getRemainingBackupCodes(User $user): int
    {
        return count($user->mfa_backup_codes ?? []);
    }

    /**
     * Regenerate backup codes
     */
    public function regenerateBackupCodes(User $user): array
    {
        $codes = $this->generateBackupCodes();
        $user->update(['mfa_backup_codes' => $codes]);
        return $codes;
    }
}
