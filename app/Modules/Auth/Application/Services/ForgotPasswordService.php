<?php

declare(strict_types=1);

namespace Modules\Auth\Application\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Modules\Auth\Application\Contracts\ForgotPasswordServiceInterface;

/**
 * Initiates the password-reset flow using Laravel's built-in broker.
 * Sends a signed reset link to the user's email address.
 *
 * Swappable via ForgotPasswordServiceInterface in AuthModuleServiceProvider.
 */
class ForgotPasswordService implements ForgotPasswordServiceInterface
{
    public function sendResetLink(string $email): bool
    {
        $status = Password::sendResetLink(['email' => $email]);

        if ($status !== Password::RESET_LINK_SENT) {
            Log::warning('Password reset link failed', [
                'email' => $email,
                'status' => $status,
            ]);

            return false;
        }

        return true;
    }
}
