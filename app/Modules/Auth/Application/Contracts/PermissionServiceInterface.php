<?php

declare(strict_types=1);

namespace Modules\Auth\Application\Contracts;

use Modules\Auth\Application\DTOs\PermissionData;

interface PermissionServiceInterface
{
    /**
     * Create a new permission (typically system-managed).
     */
    public function create(PermissionData $dto): mixed;

    /**
     * Find a permission by its ID.
     */
    public function find(int $id): mixed;

    /**
     * Find a permission by its slug.
     */
    public function findBySlug(string $slug): mixed;

    /**
     * List all permissions, optionally filtered by module.
     */
    public function list(array $filters = [], ?int $perPage = null): mixed;

    /**
     * Update a permission.
     */
    public function update(int $id, array $data): mixed;

    /**
     * Delete a permission.
     */
    public function delete(int $id): bool;
}
