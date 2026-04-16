<?php

declare(strict_types=1);

namespace Modules\Auth\Application\Contracts;

/**
 * Contract for completing the password reset flow.
 * Validates the token and updates the user's password.
 */
interface ResetPasswordServiceInterface
{
    /**
     * Reset the user's password using the provided token and credentials.
     *
     * @param  array{email: string, password: string, password_confirmation: string, token: string}  $data
     *
     * @throws \Modules\Auth\Domain\Exceptions\AuthenticationException  When the token is invalid or expired.
     */
    public function reset(array $data): bool;
}
