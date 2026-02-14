<?php

namespace App\Modules\User\Services;

use App\Core\Services\BaseService;
use App\Modules\User\Repositories\UserRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class UserService extends BaseService
{
    /**
     * UserService constructor
     */
    public function __construct(UserRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Create user with password hashing
     */
    public function create(array $data)
    {
        DB::beginTransaction();

        try {
            if (isset($data['password'])) {
                $data['password'] = Hash::make($data['password']);
            }

            $user = $this->repository->create($data);

            if (isset($data['role'])) {
                $user->assignRole($data['role']);
            }

            if (isset($data['permissions'])) {
                $user->givePermissionTo($data['permissions']);
            }

            DB::commit();

            Log::info("User {$user->id} created successfully");

            return $user;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating user: '.$e->getMessage());
            throw $e;
        }
    }

    /**
     * Assign role to user
     */
    public function assignRole(int $userId, string|array $roles): bool
    {
        DB::beginTransaction();

        try {
            $user = $this->repository->findOrFail($userId);

            $user->syncRoles([]);
            $user->assignRole($roles);

            DB::commit();

            Log::info("Role(s) assigned to user {$userId}");

            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error assigning role to user {$userId}: ".$e->getMessage());
            throw $e;
        }
    }

    /**
     * Sync permissions for user
     */
    public function syncPermissions(int $userId, array $permissions): bool
    {
        DB::beginTransaction();

        try {
            $user = $this->repository->findOrFail($userId);

            $user->syncPermissions($permissions);

            DB::commit();

            Log::info("Permissions synced for user {$userId}");

            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error syncing permissions for user {$userId}: ".$e->getMessage());
            throw $e;
        }
    }

    /**
     * Update user password
     */
    public function updatePassword(int $userId, string $password): bool
    {
        DB::beginTransaction();

        try {
            $result = $this->repository->update($userId, [
                'password' => Hash::make($password),
                'password_updated_at' => now(),
            ]);

            DB::commit();

            Log::info("Password updated for user {$userId}");

            return $result;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error updating password for user {$userId}: ".$e->getMessage());
            throw $e;
        }
    }

    /**
     * Get active users
     */
    public function getActive()
    {
        try {
            return $this->repository->getActive();
        } catch (\Exception $e) {
            Log::error('Error fetching active users: '.$e->getMessage());
            throw $e;
        }
    }

    /**
     * Get users by role
     */
    public function getByRole(string $role)
    {
        try {
            return $this->repository->getByRole($role);
        } catch (\Exception $e) {
            Log::error("Error fetching users by role {$role}: ".$e->getMessage());
            throw $e;
        }
    }

    /**
     * Search users
     */
    public function search(string $term)
    {
        try {
            return $this->repository->search($term);
        } catch (\Exception $e) {
            Log::error("Error searching users with term '{$term}': ".$e->getMessage());
            throw $e;
        }
    }

    /**
     * Deactivate user
     */
    public function deactivate(int $userId): bool
    {
        DB::beginTransaction();

        try {
            $result = $this->repository->update($userId, [
                'is_active' => false,
                'deactivated_at' => now(),
            ]);

            DB::commit();

            Log::info("User {$userId} deactivated");

            return $result;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error deactivating user {$userId}: ".$e->getMessage());
            throw $e;
        }
    }

    /**
     * Activate user
     */
    public function activate(int $userId): bool
    {
        DB::beginTransaction();

        try {
            $result = $this->repository->update($userId, [
                'is_active' => true,
                'activated_at' => now(),
            ]);

            DB::commit();

            Log::info("User {$userId} activated");

            return $result;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error activating user {$userId}: ".$e->getMessage());
            throw $e;
        }
    }
}
