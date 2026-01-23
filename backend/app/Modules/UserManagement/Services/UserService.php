<?php

namespace App\Modules\UserManagement\Services;

use App\Core\Base\BaseService;
use App\Modules\UserManagement\Repositories\UserRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;

class UserService extends BaseService
{
    public function __construct(UserRepository $repository)
    {
        parent::__construct($repository);
    }

    public function create(array $data): \Illuminate\Database\Eloquent\Model
    {
        // Hash password if provided
        if (!empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        return parent::create($data);
    }

    public function update(int $id, array $data): \Illuminate\Database\Eloquent\Model
    {
        // Hash password if provided
        if (!empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        return parent::update($id, $data);
    }

    protected function afterCreate($user, array $data): void
    {
        Log::info('User created', ['user_id' => $user->id, 'email' => $user->email]);
        
        // Assign roles if provided
        if (!empty($data['roles'])) {
            $user->assignRole($data['roles']);
        }

        // Assign permissions if provided
        if (!empty($data['permissions'])) {
            $user->givePermissionTo($data['permissions']);
        }
    }

    protected function afterUpdate($user, array $data): void
    {
        Log::info('User updated', ['user_id' => $user->id]);

        // Update roles if provided
        if (isset($data['roles'])) {
            $user->syncRoles($data['roles']);
        }

        // Update permissions if provided
        if (isset($data['permissions'])) {
            $user->syncPermissions($data['permissions']);
        }
    }

    public function findByEmail(string $email)
    {
        return $this->repository->findByEmail($email);
    }

    public function getForTenant(int $tenantId)
    {
        return $this->repository->getForTenant($tenantId);
    }

    public function getActive()
    {
        return $this->repository->getActive();
    }

    public function search(array $criteria)
    {
        return $this->repository->search($criteria);
    }

    public function activate(int $id)
    {
        try {
            DB::beginTransaction();

            $user = $this->repository->update($id, ['status' => 'active']);
            
            DB::commit();

            Log::info('User activated', ['user_id' => $id]);
            return $user;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to activate user', ['user_id' => $id, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function deactivate(int $id)
    {
        try {
            DB::beginTransaction();

            $user = $this->repository->update($id, ['status' => 'inactive']);
            
            // Revoke all tokens
            $user = $this->repository->findById($id);
            $user->tokens()->delete();
            
            DB::commit();

            Log::info('User deactivated', ['user_id' => $id]);
            return $user;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to deactivate user', ['user_id' => $id, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function assignRoles(int $id, array $roles)
    {
        try {
            $user = $this->repository->findById($id);
            $user->syncRoles($roles);

            Log::info('User roles updated', ['user_id' => $id, 'roles' => $roles]);
            return $user;
        } catch (\Exception $e) {
            Log::error('Failed to assign roles', ['user_id' => $id, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function assignPermissions(int $id, array $permissions)
    {
        try {
            $user = $this->repository->findById($id);
            $user->syncPermissions($permissions);

            Log::info('User permissions updated', ['user_id' => $id, 'permissions' => $permissions]);
            return $user;
        } catch (\Exception $e) {
            Log::error('Failed to assign permissions', ['user_id' => $id, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function countByTenant(int $tenantId): int
    {
        return $this->repository->countByTenant($tenantId);
    }
}
