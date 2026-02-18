<?php

declare(strict_types=1);

namespace Modules\IAM\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\IAM\Entities\Group;
use Modules\IAM\Repositories\Contracts\GroupRepositoryInterface;

class GroupRepository implements GroupRepositoryInterface
{
    public function __construct(
        protected Group $model
    ) {}

    /**
     * Find group by ID.
     */
    public function findById(int $id): ?Group
    {
        return $this->model->find($id);
    }

    /**
     * Find group by slug.
     */
    public function findBySlug(string $slug): ?Group
    {
        return $this->model->where('slug', $slug)->first();
    }

    /**
     * Get all groups.
     */
    public function all(): Collection
    {
        return $this->model->all();
    }

    /**
     * Get paginated groups.
     */
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->paginate($perPage);
    }

    /**
     * Create a new group.
     */
    public function create(array $data): Group
    {
        return $this->model->create($data);
    }

    /**
     * Update a group.
     */
    public function update(Group $group, array $data): Group
    {
        $group->update($data);

        return $group->fresh();
    }

    /**
     * Delete a group.
     */
    public function delete(Group $group): bool
    {
        return $group->delete();
    }

    /**
     * Get active groups.
     */
    public function findActive(): Collection
    {
        return $this->model->active()->get();
    }

    /**
     * Get root groups (no parent).
     */
    public function findRoots(): Collection
    {
        return $this->model->root()->get();
    }

    /**
     * Search groups by name.
     */
    public function search(string $query): Collection
    {
        return $this->model
            ->where('name', 'LIKE', "%{$query}%")
            ->orWhere('description', 'LIKE', "%{$query}%")
            ->get();
    }
}
