<?php

namespace App\Repositories\Contracts;

interface UserRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Find a user by email address.
     */
    public function findByEmail(string $email): ?\Illuminate\Database\Eloquent\Model;

    /**
     * Retrieve all users belonging to a specific tenant.
     */
    public function getByTenant(int|string $tenantId): \Illuminate\Database\Eloquent\Collection;

    /**
     * Attach one or more roles to a user.
     */
    public function assignRoles(int|string $userId, array $roleIds): void;

    /**
     * Remove all roles from a user and optionally assign fresh ones.
     */
    public function syncRoles(int|string $userId, array $roleIds): void;

    /**
     * Detach one or more roles from a user.
     */
    public function revokeRoles(int|string $userId, array $roleIds): void;

    /**
     * Attach one or more permissions directly to a user.
     */
    public function assignPermissions(int|string $userId, array $permissionIds): void;

    /**
     * Remove all direct permissions from a user and optionally assign fresh ones.
     */
    public function syncPermissions(int|string $userId, array $permissionIds): void;

    /**
     * Find users that have a given role name.
     */
    public function findByRole(string $roleName): \Illuminate\Database\Eloquent\Collection;

    /**
     * Retrieve users matching a status flag within a tenant.
     */
    public function getActiveUsers(int|string $tenantId): \Illuminate\Database\Eloquent\Collection;

    /**
     * Hard-delete all users associated with a tenant (used during tenant teardown).
     */
    public function deleteByTenant(int|string $tenantId): int;
}
