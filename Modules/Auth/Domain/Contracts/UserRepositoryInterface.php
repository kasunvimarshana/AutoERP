<?php

declare(strict_types=1);

namespace Modules\Auth\Domain\Contracts;

use Modules\Auth\Domain\Entities\User;
use Modules\Auth\Domain\ValueObjects\Email;

interface UserRepositoryInterface
{
    public function findById(int $id): ?User;

    public function findByEmail(Email $email, int $tenantId): ?User;

    /** Find an active user by email (no tenant filter — used for login). */
    public function findActiveByEmail(Email $email): ?User;

    public function save(User $user): User;

    public function delete(int $id): void;

    /** Record the login timestamp for the given user ID. */
    public function updateLastLoginAt(int $id): void;
}
