<?php

namespace Modules\IAM\Services;

use PragmaRX\Google2FA\Google2FA;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Modules\IAM\Models\User;

/**
 * Multi-Factor Authentication Service
 * Handles TOTP-based MFA using Google Authenticator
 */
class MfaService
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
     * Generate QR code URL for Google Authenticator
     */
    public function getQrCodeUrl(User $user, string $secret): string
    {
        $appName = config('app.name', 'AutoERP');
        
        return $this->google2fa->getQRCodeUrl(
            $appName,
            $user->email,
            $secret
        );
    }

    /**
     * Generate QR code SVG for display
     */
    public function getQrCodeSvg(User $user, string $secret): string
    {
        $qrCodeUrl = $this->getQrCodeUrl($user, $secret);
        
        $renderer = new ImageRenderer(
            new RendererStyle(200),
            new SvgImageBackEnd()
        );
        
        $writer = new Writer($renderer);
        
        return $writer->writeString($qrCodeUrl);
    }

    /**
     * Verify a TOTP code against the user's secret
     */
    public function verifyCode(string $secret, string $code): bool
    {
        return $this->google2fa->verifyKey($secret, $code);
    }

    /**
     * Get backup codes for the user (for recovery)
     * These should be securely stored and shown once
     */
    public function generateBackupCodes(int $count = 10): array
    {
        $codes = [];
        
        for ($i = 0; $i < $count; $i++) {
            $codes[] = strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
        }
        
        return $codes;
    }

    /**
     * Verify a backup code
     */
    public function verifyBackupCode(User $user, string $code): bool
    {
        $backupCodes = $user->getBackupCodes();
        
        if (!$backupCodes) {
            return false;
        }
        
        $code = strtoupper($code);
        
        if (in_array($code, $backupCodes, true)) {
            // Remove used backup code
            $remainingCodes = array_diff($backupCodes, [$code]);
            $user->setBackupCodes($remainingCodes);
            $user->save();
            
            return true;
        }
        
        return false;
    }

    /**
     * Enable MFA for a user
     */
    public function enableMfa(User $user, string $secret, array $backupCodes): void
    {
        $user->enableMfa($secret);
        $user->setBackupCodes($backupCodes);
        $user->save();
    }

    /**
     * Disable MFA for a user
     */
    public function disableMfa(User $user): void
    {
        $user->disableMfa();
        $user->setBackupCodes([]);
        $user->save();
    }
}
