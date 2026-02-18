<?php

declare(strict_types=1);

namespace Modules\IAM\Repositories\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\IAM\Entities\Group;

interface GroupRepositoryInterface
{
    /**
     * Find group by ID.
     */
    public function findById(int $id): ?Group;

    /**
     * Find group by slug.
     */
    public function findBySlug(string $slug): ?Group;

    /**
     * Get all groups.
     */
    public function all(): Collection;

    /**
     * Get paginated groups.
     */
    public function paginate(int $perPage = 15): LengthAwarePaginator;

    /**
     * Create a new group.
     */
    public function create(array $data): Group;

    /**
     * Update a group.
     */
    public function update(Group $group, array $data): Group;

    /**
     * Delete a group.
     */
    public function delete(Group $group): bool;

    /**
     * Get active groups.
     */
    public function findActive(): Collection;

    /**
     * Get root groups (no parent).
     */
    public function findRoots(): Collection;

    /**
     * Search groups by name.
     */
    public function search(string $query): Collection;
}
