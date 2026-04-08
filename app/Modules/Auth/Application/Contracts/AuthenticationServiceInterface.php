<?php

declare(strict_types=1);

namespace Modules\Auth\Application\Contracts;

use Modules\Auth\Domain\Entities\AccessToken;
use Modules\Auth\Domain\Exceptions\InvalidCredentialsException;

/**
 * Contract for the main authentication service.
 * Handles credential validation, token issuance, and session management.
 */
interface AuthenticationServiceInterface
{
    /**
     * Authenticate user credentials and return an access token.
     *
     *
     * @throws InvalidCredentialsException
     */
    public function authenticate(string $email, string $password): AccessToken;

    /**
     * Invalidate the currently authenticated user's token.
     */
    public function invalidate(int $userId): bool;
}
