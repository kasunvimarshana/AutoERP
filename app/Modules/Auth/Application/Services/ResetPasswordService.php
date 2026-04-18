<?php

declare(strict_types=1);

namespace Modules\Auth\Application\Services;

use Illuminate\Support\Facades\Password;
use Modules\Auth\Application\Contracts\ResetPasswordServiceInterface;
use Modules\Auth\Domain\Exceptions\AuthenticationException;
use Modules\User\Application\Contracts\SetUserPasswordServiceInterface;

/**
 * Completes the password-reset flow using Laravel's built-in broker.
 * Validates the token and hashes the new password before persisting.
 *
 * Swappable via ResetPasswordServiceInterface in AuthModuleServiceProvider.
 */
class ResetPasswordService implements ResetPasswordServiceInterface
{
    public function __construct(
        private readonly SetUserPasswordServiceInterface $setUserPasswordService,
    ) {}

    public function reset(array $data): bool
    {
        $status = Password::reset(
            $data,
            function ($user, string $password): void {
                $userId = (int) $user->getAuthIdentifier();
                if ($userId <= 0) {
                    throw new AuthenticationException('Unable to resolve user for password reset.');
                }

                $this->setUserPasswordService->execute([
                    'user_id' => $userId,
                    'password' => $password,
                ]);
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
