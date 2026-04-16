<?php

declare(strict_types=1);

namespace Modules\Auth\Application\Services;

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

        return $status === Password::RESET_LINK_SENT;
    }
}
