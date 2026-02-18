<?php

declare(strict_types=1);

namespace Modules\IAM\Services;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\IAM\Entities\Group;
use Modules\IAM\Repositories\Contracts\GroupRepositoryInterface;

/**
 * Group Service
 *
 * Handles business logic for group/team management operations.
 */
class GroupService
{
    /**
     * GroupService constructor.
     */
    public function __construct(
        protected GroupRepositoryInterface $groupRepository
    ) {}

    /**
     * Get paginated list of groups.
     */
    public function getPaginated(int $perPage = 15): LengthAwarePaginator
    {
        return $this->groupRepository->paginate($perPage);
    }

    /**
     * Get all active groups.
     */
    public function getAllActive(): Collection
    {
        return $this->groupRepository->getActive();
    }

    /**
     * Get root groups (no parent).
     */
    public function getRootGroups(): Collection
    {
        return $this->groupRepository->getRootGroups();
    }

    /**
     * Get group hierarchy tree.
     */
    public function getGroupTree(): Collection
    {
        return $this->groupRepository->getRootGroups()->load('children');
    }

    /**
     * Find a group by ID.
     */
    public function findById(int $id): ?Group
    {
        return $this->groupRepository->findById($id);
    }

    /**
     * Find a group by slug.
     */
    public function findBySlug(string $slug): ?Group
    {
        return $this->groupRepository->findBySlug($slug);
    }

    /**
     * Create a new group.
     *
     * @throws \Exception
     */
    public function create(array $data): Group
    {
        DB::beginTransaction();
        try {
            // Validate business rules
            $this->validateGroupCreation($data);

            // Generate slug if not provided
            if (!isset($data['slug']) || empty($data['slug'])) {
                $data['slug'] = Str::slug($data['name']);
            }

            // Validate parent group if provided
            if (!empty($data['parent_id'])) {
                $parent = $this->groupRepository->findById($data['parent_id']);
                if (!$parent) {
                    throw new \Exception("Parent group not found: {$data['parent_id']}");
                }

                // Prevent creating circular references
                if ($parent->parent_id && isset($data['id']) && $parent->parent_id == $data['id']) {
                    throw new \Exception('Cannot create circular parent-child relationship');
                }
            }

            $group = $this->groupRepository->create($data);

            DB::commit();
            return $group;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Update an existing group.
     *
     * @throws \Exception
     */
    public function update(int $id, array $data): Group
    {
        DB::beginTransaction();
        try {
            $group = $this->groupRepository->findById($id);

            if (!$group) {
                throw new \Exception("Group not found: {$id}");
            }

            // Validate business rules
            $this->validateGroupUpdate($group, $data);

            // Update slug if name changed
            if (isset($data['name']) && $data['name'] !== $group->name) {
                if (!isset($data['slug'])) {
                    $data['slug'] = Str::slug($data['name']);
                }
            }

            // Validate parent group if being changed
            if (isset($data['parent_id']) && $data['parent_id'] !== $group->parent_id) {
                if (!empty($data['parent_id'])) {
                    $parent = $this->groupRepository->findById($data['parent_id']);
                    if (!$parent) {
                        throw new \Exception("Parent group not found: {$data['parent_id']}");
                    }

                    // Prevent creating circular references
                    if ($parent->id === $group->id) {
                        throw new \Exception('A group cannot be its own parent');
                    }

                    // Check if new parent is a descendant of this group
                    if ($this->isDescendant($group->id, $parent->id)) {
                        throw new \Exception('Cannot set a descendant group as parent (circular reference)');
                    }
                }
            }

            $group = $this->groupRepository->update($group, $data);

            DB::commit();
            return $group;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Delete a group.
     *
     * @throws \Exception
     */
    public function delete(int $id): bool
    {
        DB::beginTransaction();
        try {
            $group = $this->groupRepository->findById($id);

            if (!$group) {
                throw new \Exception("Group not found: {$id}");
            }

            // Check if group has child groups
            if ($group->children()->count() > 0) {
                throw new \Exception('Cannot delete group that has child groups. Delete or reassign child groups first.');
            }

            // Check if group has users
            if ($group->users()->count() > 0) {
                throw new \Exception('Cannot delete group that has users. Remove all users first.');
            }

            $result = $this->groupRepository->delete($group);

            DB::commit();
            return $result;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Add a user to a group.
     *
     * @throws \Exception
     */
    public function addUser(int $groupId, int $userId): Group
    {
        DB::beginTransaction();
        try {
            $group = $this->groupRepository->findById($groupId);

            if (!$group) {
                throw new \Exception("Group not found: {$groupId}");
            }

            $group->addUser($userId);

            DB::commit();
            return $group->fresh();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Remove a user from a group.
     *
     * @throws \Exception
     */
    public function removeUser(int $groupId, int $userId): Group
    {
        DB::beginTransaction();
        try {
            $group = $this->groupRepository->findById($groupId);

            if (!$group) {
                throw new \Exception("Group not found: {$groupId}");
            }

            $group->removeUser($userId);

            DB::commit();
            return $group->fresh();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Assign a role to a group.
     *
     * @throws \Exception
     */
    public function assignRole(int $groupId, int $roleId): Group
    {
        DB::beginTransaction();
        try {
            $group = $this->groupRepository->findById($groupId);

            if (!$group) {
                throw new \Exception("Group not found: {$groupId}");
            }

            $group->assignRole($roleId);

            DB::commit();
            return $group->fresh();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Remove a role from a group.
     *
     * @throws \Exception
     */
    public function removeRole(int $groupId, int $roleId): Group
    {
        DB::beginTransaction();
        try {
            $group = $this->groupRepository->findById($groupId);

            if (!$group) {
                throw new \Exception("Group not found: {$groupId}");
            }

            $group->removeRole($roleId);

            DB::commit();
            return $group->fresh();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Validate group creation.
     *
     * @throws \Exception
     */
    protected function validateGroupCreation(array $data): void
    {
        // Check for duplicate slug
        if (isset($data['slug'])) {
            $existing = $this->groupRepository->findBySlug($data['slug']);
            if ($existing) {
                throw new \Exception("Group with slug '{$data['slug']}' already exists");
            }
        }
    }

    /**
     * Validate group update.
     *
     * @throws \Exception
     */
    protected function validateGroupUpdate(Group $group, array $data): void
    {
        // Check for duplicate slug (if slug is being changed)
        if (isset($data['slug']) && $data['slug'] !== $group->slug) {
            $existing = $this->groupRepository->findBySlug($data['slug']);
            if ($existing && $existing->id !== $group->id) {
                throw new \Exception("Group with slug '{$data['slug']}' already exists");
            }
        }
    }

    /**
     * Check if a group is a descendant of another group.
     */
    protected function isDescendant(int $groupId, int $potentialDescendantId): bool
    {
        $group = $this->groupRepository->findById($potentialDescendantId);

        if (!$group) {
            return false;
        }

        // Check immediate parent
        if ($group->parent_id === $groupId) {
            return true;
        }

        // Recursively check parent's ancestors
        if ($group->parent_id) {
            return $this->isDescendant($groupId, $group->parent_id);
        }

        return false;
    }
}
