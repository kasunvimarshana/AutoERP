<?php

declare(strict_types=1);

namespace Modules\IAM\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\IAM\Services\MFA\MFAService;

/**
 * MFA Controller
 * 
 * Handles multi-factor authentication endpoints
 */
class MFAController extends Controller
{
    public function __construct(private readonly MFAService $mfaService)
    {
    }

    /**
     * Get MFA setup information
     */
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();
        
        return response()->json([
            'mfa_enabled' => $user->mfa_enabled,
            'backup_codes_remaining' => $this->mfaService->getRemainingBackupCodes($user),
        ]);
    }

    /**
     * Generate QR code for MFA setup
     */
    public function setup(Request $request): JsonResponse
    {
        $user = $request->user();
        
        if ($user->mfa_enabled) {
            return response()->json([
                'error' => 'MFA is already enabled',
            ], 400);
        }

        $secret = $this->mfaService->generateSecret();
        $qrCode = $this->mfaService->getQRCode($user, $secret);

        return response()->json([
            'secret' => $secret,
            'qr_code' => $qrCode,
        ]);
    }

    /**
     * Enable MFA after verification
     */
    public function enable(Request $request): JsonResponse
    {
        $request->validate([
            'secret' => 'required|string',
            'code' => 'required|string|size:6',
        ]);

        $user = $request->user();
        
        if ($user->mfa_enabled) {
            return response()->json([
                'error' => 'MFA is already enabled',
            ], 400);
        }

        // Temporarily set the secret to verify the code
        $user->mfa_secret = encrypt($request->secret);
        $user->mfa_enabled = true;

        if (!$this->mfaService->verifyCode($user, $request->code)) {
            $user->mfa_secret = null;
            $user->mfa_enabled = false;
            
            return response()->json([
                'error' => 'Invalid verification code',
            ], 422);
        }

        // Enable MFA and generate backup codes
        $this->mfaService->enableMFA($user, $request->secret);

        return response()->json([
            'message' => 'MFA enabled successfully',
            'backup_codes' => $user->fresh()->mfa_backup_codes,
        ]);
    }

    /**
     * Disable MFA
     */
    public function disable(Request $request): JsonResponse
    {
        $request->validate([
            'code' => 'required|string',
        ]);

        $user = $request->user();
        
        if (!$user->mfa_enabled) {
            return response()->json([
                'error' => 'MFA is not enabled',
            ], 400);
        }

        // Verify code before disabling
        if (!$this->mfaService->verifyCode($user, $request->code)) {
            return response()->json([
                'error' => 'Invalid verification code',
            ], 422);
        }

        $this->mfaService->disableMFA($user);

        return response()->json([
            'message' => 'MFA disabled successfully',
        ]);
    }

    /**
     * Regenerate backup codes
     */
    public function regenerateBackupCodes(Request $request): JsonResponse
    {
        $request->validate([
            'code' => 'required|string',
        ]);

        $user = $request->user();
        
        if (!$user->mfa_enabled) {
            return response()->json([
                'error' => 'MFA is not enabled',
            ], 400);
        }

        // Verify code before regenerating
        if (!$this->mfaService->verifyCode($user, $request->code)) {
            return response()->json([
                'error' => 'Invalid verification code',
            ], 422);
        }

        $backupCodes = $this->mfaService->regenerateBackupCodes($user);

        return response()->json([
            'message' => 'Backup codes regenerated successfully',
            'backup_codes' => $backupCodes,
        ]);
    }

    /**
     * Verify MFA code during login
     */
    public function verify(Request $request): JsonResponse
    {
        $request->validate([
            'code' => 'required|string',
        ]);

        $user = $request->user();
        
        if (!$user->mfa_enabled) {
            return response()->json([
                'error' => 'MFA is not enabled',
            ], 400);
        }

        if (!$this->mfaService->verifyCode($user, $request->code)) {
            return response()->json([
                'error' => 'Invalid verification code',
            ], 422);
        }

        return response()->json([
            'message' => 'MFA verification successful',
            'verified' => true,
        ]);
    }
}
