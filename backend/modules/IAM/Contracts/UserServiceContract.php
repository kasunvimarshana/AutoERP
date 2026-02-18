<?php

declare(strict_types=1);

namespace Modules\IAM\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\IAM\DTOs\ChangePasswordDTO;
use Modules\IAM\DTOs\UserDTO;
use Modules\IAM\Models\User;

/**
 * User Service Contract
 *
 * Defines operations for user management in the IAM module.
 * Ensures consistent interface for user-related business logic.
 *
 * @package Modules\IAM\Contracts
 */
interface UserServiceContract
{
    /**
     * Create new user
     *
     * @param UserDTO $dto
     * @return User
     * @throws \Illuminate\Validation\ValidationException
     */
    public function create(UserDTO $dto): User;

    /**
     * Update existing user
     *
     * @param int|string $id
     * @param UserDTO $dto
     * @return User
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function update($id, UserDTO $dto): User;

    /**
     * Delete user
     *
     * @param int|string $id
     * @return bool
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function delete($id): bool;

    /**
     * Find user by ID
     *
     * @param int|string $id
     * @return User|null
     */
    public function find($id): ?User;

    /**
     * Get paginated user list
     *
     * @param array<string, mixed> $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function list(array $filters = [], int $perPage = 15): LengthAwarePaginator;

    /**
     * Assign roles to user
     *
     * @param int|string $userId
     * @param array<string> $roles
     * @return User
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function assignRoles($userId, array $roles): User;

    /**
     * Assign permissions to user
     *
     * @param int|string $userId
     * @param array<string> $permissions
     * @return User
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function assignPermissions($userId, array $permissions): User;

    /**
     * Change user password
     *
     * @param int|string $userId
     * @param ChangePasswordDTO $dto
     * @return bool
     * @throws \Illuminate\Validation\ValidationException
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function changePassword($userId, ChangePasswordDTO $dto): bool;

    /**
     * Activate user account
     *
     * @param int|string $userId
     * @return User
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function activate($userId): User;

    /**
     * Deactivate user account
     *
     * @param int|string $userId
     * @return User
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function deactivate($userId): User;
}
