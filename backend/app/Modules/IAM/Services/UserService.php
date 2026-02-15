<?php

declare(strict_types=1);

namespace App\Modules\IAM\Services;

use App\Models\User;
use App\Modules\IAM\Repositories\UserRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class UserService
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository
    ) {}

    /**
     * Get paginated list of users.
     *
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getUsers(int $perPage = 15): LengthAwarePaginator
    {
        return $this->userRepository->paginate($perPage);
    }

    /**
     * Get a user by ID.
     *
     * @param int $id
     * @return User|null
     */
    public function getUser(int $id): ?User
    {
        return $this->userRepository->find($id);
    }

    /**
     * Get a user by UUID.
     *
     * @param string $uuid
     * @return User|null
     */
    public function getUserByUuid(string $uuid): ?User
    {
        return $this->userRepository->findByUuid($uuid);
    }

    /**
     * Create a new user with transaction safety.
     *
     * @param array $data
     * @return User
     * @throws \Exception
     */
    public function createUser(array $data): User
    {
        try {
            DB::beginTransaction();

            // Hash password if provided
            if (isset($data['password'])) {
                $data['password'] = Hash::make($data['password']);
            }

            // Set default status if not provided
            $data['status'] = $data['status'] ?? 'active';

            // Create user
            $user = $this->userRepository->create($data);

            // Assign roles if provided
            if (isset($data['roles']) && is_array($data['roles'])) {
                $this->userRepository->assignRoles($user, $data['roles']);
            }

            // Assign permissions if provided
            if (isset($data['permissions']) && is_array($data['permissions'])) {
                $this->userRepository->assignPermissions($user, $data['permissions']);
            }

            DB::commit();

            return $user->fresh(['tenant', 'organization', 'branch', 'roles', 'permissions']);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Update a user with transaction safety.
     *
     * @param int $id
     * @param array $data
     * @return User
     * @throws \Exception
     */
    public function updateUser(int $id, array $data): User
    {
        try {
            DB::beginTransaction();

            $user = $this->userRepository->find($id);

            if (!$user) {
                throw new \Exception('User not found');
            }

            // Hash password if provided and changed
            if (isset($data['password']) && !empty($data['password'])) {
                $data['password'] = Hash::make($data['password']);
            } else {
                unset($data['password']);
            }

            // Update user
            $user = $this->userRepository->update($user, $data);

            // Sync roles if provided
            if (isset($data['roles']) && is_array($data['roles'])) {
                $this->userRepository->syncRoles($user, $data['roles']);
            }

            // Assign permissions if provided
            if (isset($data['permissions']) && is_array($data['permissions'])) {
                // Clear existing direct permissions first
                $user->permissions()->detach();
                $this->userRepository->assignPermissions($user, $data['permissions']);
            }

            DB::commit();

            return $user->fresh(['tenant', 'organization', 'branch', 'roles', 'permissions']);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Delete a user (soft delete).
     *
     * @param int $id
     * @return bool
     * @throws \Exception
     */
    public function deleteUser(int $id): bool
    {
        $user = $this->userRepository->find($id);

        if (!$user) {
            throw new \Exception('User not found');
        }

        // Prevent self-deletion
        if ($user->id === auth()->id()) {
            throw ValidationException::withMessages([
                'user' => ['You cannot delete yourself']
            ]);
        }

        return $this->userRepository->delete($user);
    }

    /**
     * Restore a soft-deleted user.
     *
     * @param int $id
     * @return bool
     */
    public function restoreUser(int $id): bool
    {
        return $this->userRepository->restore($id);
    }

    /**
     * Search users.
     *
     * @param array $criteria
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function searchUsers(array $criteria, int $perPage = 15): LengthAwarePaginator
    {
        return $this->userRepository->search($criteria, $perPage);
    }

    /**
     * Get users by role.
     *
     * @param string $roleName
     * @return Collection
     */
    public function getUsersByRole(string $roleName): Collection
    {
        return $this->userRepository->getByRole($roleName);
    }

    /**
     * Get users by organization.
     *
     * @param int $organizationId
     * @return Collection
     */
    public function getUsersByOrganization(int $organizationId): Collection
    {
        return $this->userRepository->getByOrganization($organizationId);
    }

    /**
     * Get users by branch.
     *
     * @param int $branchId
     * @return Collection
     */
    public function getUsersByBranch(int $branchId): Collection
    {
        return $this->userRepository->getByBranch($branchId);
    }

    /**
     * Get active users.
     *
     * @return Collection
     */
    public function getActiveUsers(): Collection
    {
        return $this->userRepository->getActiveUsers();
    }

    /**
     * Assign roles to user.
     *
     * @param int $userId
     * @param array $roleNames
     * @return User
     * @throws \Exception
     */
    public function assignRolesToUser(int $userId, array $roleNames): User
    {
        $user = $this->userRepository->find($userId);

        if (!$user) {
            throw new \Exception('User not found');
        }

        return $this->userRepository->assignRoles($user, $roleNames);
    }

    /**
     * Update user profile.
     *
     * @param int $userId
     * @param array $data
     * @return User
     * @throws \Exception
     */
    public function updateProfile(int $userId, array $data): User
    {
        $user = $this->userRepository->find($userId);

        if (!$user) {
            throw new \Exception('User not found');
        }

        // Only allow specific profile fields
        $allowedFields = ['name', 'phone', 'avatar', 'timezone', 'language_code'];
        $profileData = array_intersect_key($data, array_flip($allowedFields));

        return $this->userRepository->update($user, $profileData);
    }

    /**
     * Change user password.
     *
     * @param int $userId
     * @param string $currentPassword
     * @param string $newPassword
     * @return User
     * @throws \Exception
     */
    public function changePassword(int $userId, string $currentPassword, string $newPassword): User
    {
        $user = $this->userRepository->find($userId);

        if (!$user) {
            throw new \Exception('User not found');
        }

        // Verify current password
        if (!Hash::check($currentPassword, $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['The provided password does not match your current password.']
            ]);
        }

        return $this->userRepository->update($user, [
            'password' => Hash::make($newPassword)
        ]);
    }
}
