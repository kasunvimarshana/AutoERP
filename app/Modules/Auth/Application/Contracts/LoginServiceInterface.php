<?php

declare(strict_types=1);

namespace Modules\Auth\Application\Contracts;

use Modules\Auth\Domain\Entities\AccessToken;
use Modules\Auth\Domain\Exceptions\InvalidCredentialsException;

/**
 * Contract for the login service.
 */
interface LoginServiceInterface
{
    /**
     * Authenticate credentials and return an access token.
     *
     * @throws InvalidCredentialsException
     */
    public function login(string $email, string $password): AccessToken;
}
