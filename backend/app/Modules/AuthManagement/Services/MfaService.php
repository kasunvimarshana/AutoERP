<?php

namespace App\Modules\AuthManagement\Services;

use App\Models\User;
use App\Modules\AuthManagement\Repositories\MfaSecretRepository;
use App\Modules\AuthManagement\Models\MfaSecret;
use App\Modules\AuthManagement\Events\MfaEnabled;
use App\Modules\AuthManagement\Events\MfaDisabled;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PragmaRX\Google2FA\Google2FA;

class MfaService
{
    public function __construct(
        protected MfaSecretRepository $mfaRepository
    ) {}

    /**
     * Setup MFA for user (generates secret and QR code)
     */
    public function setupMfa(User $user, string $type = 'totp'): array
    {
        try {
            DB::beginTransaction();

            $mfaSecret = $this->mfaRepository->findOrCreate($user->id, $type);

            if ($type === 'totp') {
                $google2fa = new Google2FA();
                $secret = $google2fa->generateSecretKey();

                $mfaSecret->update(['secret' => $secret]);

                // Generate QR code URL
                $qrCodeUrl = $google2fa->getQRCodeUrl(
                    config('app.name'),
                    $user->email,
                    $secret
                );

                // Generate recovery codes
                $recoveryCodes = $mfaSecret->generateRecoveryCodes();
                $mfaSecret->update(['recovery_codes' => $recoveryCodes]);

                DB::commit();

                return [
                    'secret' => $secret,
                    'qr_code_url' => $qrCodeUrl,
                    'recovery_codes' => $recoveryCodes,
                ];
            }

            DB::commit();

            return [
                'type' => $type,
                'setup_required' => true,
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('MFA setup failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Enable MFA after verification
     */
    public function enableMfa(User $user, string $code, string $type = 'totp'): bool
    {
        try {
            DB::beginTransaction();

            $mfaSecret = $this->mfaRepository->findByUserAndType($user->id, $type);

            if (!$mfaSecret) {
                DB::rollBack();
                return false;
            }

            // Verify the code
            if (!$this->verifyCode($user, $code, $type)) {
                DB::rollBack();
                return false;
            }

            // Enable MFA
            $mfaSecret->update([
                'is_enabled' => true,
                'enabled_at' => now(),
                'last_used_at' => now(),
            ]);

            // Update user
            $user->update(['mfa_enabled' => true]);

            DB::commit();

            // Dispatch event
            event(new MfaEnabled($user, $type));

            Log::info('MFA enabled', [
                'user_id' => $user->id,
                'type' => $type
            ]);

            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('MFA enablement failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Disable MFA for user
     */
    public function disableMfa(User $user, string $type = 'totp'): bool
    {
        try {
            DB::beginTransaction();

            $this->mfaRepository->disableAllForUser($user->id);

            // Update user
            $user->update(['mfa_enabled' => false]);

            DB::commit();

            // Dispatch event
            event(new MfaDisabled($user, $type));

            Log::info('MFA disabled', ['user_id' => $user->id]);

            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('MFA disablement failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Verify MFA code
     */
    public function verifyCode(User $user, string $code, string $type = 'totp'): bool
    {
        $mfaSecret = $this->mfaRepository->findByUserAndType($user->id, $type);

        if (!$mfaSecret || !$mfaSecret->is_enabled) {
            return false;
        }

        if ($type === 'totp') {
            // Check if it's a recovery code
            if (strlen($code) === 8 && ctype_alnum($code)) {
                if ($mfaSecret->verifyRecoveryCode($code)) {
                    $mfaSecret->update(['last_used_at' => now()]);
                    return true;
                }
            }

            // Verify TOTP code
            $google2fa = new Google2FA();
            $valid = $google2fa->verifyKey($mfaSecret->secret, $code);

            if ($valid) {
                $mfaSecret->update(['last_used_at' => now()]);
            }

            return $valid;
        }

        return false;
    }

    /**
     * Get MFA status for user
     */
    public function getMfaStatus(User $user): array
    {
        $enabledMethods = $this->mfaRepository->getEnabledMethods($user->id);

        return [
            'enabled' => $user->mfa_enabled,
            'methods' => $enabledMethods->map(function ($method) {
                return [
                    'type' => $method->type,
                    'enabled_at' => $method->enabled_at,
                    'last_used_at' => $method->last_used_at,
                ];
            }),
        ];
    }
}
