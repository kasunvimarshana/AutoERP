<?php

declare(strict_types=1);

namespace Modules\Auth\Application\Services;

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Modules\Auth\Application\Contracts\ResetPasswordServiceInterface;
use Modules\Auth\Domain\Exceptions\AuthenticationException;

/**
 * Completes the password-reset flow using Laravel's built-in broker.
 * Validates the token and hashes the new password before persisting.
 *
 * Swappable via ResetPasswordServiceInterface in AuthModuleServiceProvider.
 */
class ResetPasswordService implements ResetPasswordServiceInterface
{
    public function reset(array $data): bool
    {
        $status = Password::reset(
            $data,
            static function ($user, string $password): void {
                $user->forceFill(['password' => Hash::make($password)])->save();
            },
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw new AuthenticationException(
                __($status) ?: 'Password reset failed. The token may be invalid or expired.',
            );
        }

        return true;
    }
}
