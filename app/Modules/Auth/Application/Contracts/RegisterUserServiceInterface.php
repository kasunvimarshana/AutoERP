<?php

declare(strict_types=1);

namespace Modules\Auth\Application\Contracts;

/**
 * Contract for user registration during authentication flow.
 */
interface RegisterUserServiceInterface
{
    /**
     * Register a new user account and return the created user's ID.
     *
     * @param  array{
     *     tenant_id: int,
     *     email: string,
     *     first_name: string,
     *     last_name: string,
     *     password: string,
     *     phone?: string|null
     * }  $data Validated registration data
     * @return int The created user's ID
     */
    public function register(array $data): int;
}
