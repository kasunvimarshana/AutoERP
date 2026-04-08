<?php

declare(strict_types=1);

namespace Modules\Auth\Application\Contracts;

use Modules\Auth\Application\DTOs\RoleData;

interface RoleServiceInterface
{
    /**
     * Create a new role.
     */
    public function create(RoleData $dto): mixed;

    /**
     * Find a role by its ID.
     */
    public function find(int $id): mixed;

    /**
     * Find a role by its slug, optionally scoped to a tenant.
     */
    public function findBySlug(string $slug, ?int $tenantId = null): mixed;

    /**
     * List roles with optional filters and pagination.
     */
    public function list(array $filters = [], ?int $perPage = null): mixed;

    /**
     * Update an existing role.
     */
    public function update(int $id, array $data): mixed;

    /**
     * Delete a role (non-system roles only).
     */
    public function delete(int $id): bool;
}
