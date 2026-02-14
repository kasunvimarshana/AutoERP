<?php

namespace App\Modules\Identity\Services;

use App\Core\BaseService;
use App\Modules\Identity\Repositories\UserRepository;
use App\Modules\Identity\Events\UserCreated;
use App\Modules\Identity\Events\UserUpdated;
use Illuminate\Support\Facades\Hash;

class UserService extends BaseService
{
    protected UserRepository $repository;

    public function __construct(UserRepository $repository)
    {
        $this->repository = $repository;
    }

    public function createUser(array $data)
    {
        return $this->transaction(function () use ($data) {
            if (isset($data['password'])) {
                $data['password'] = Hash::make($data['password']);
            }
            
            $user = $this->repository->create($data);
            
            if (isset($data['roles'])) {
                $user->assignRole($data['roles']);
            }
            
            if (isset($data['permissions'])) {
                $user->givePermissionTo($data['permissions']);
            }
            
            $this->logActivity('User created', ['user_id' => $user->id]);
            
            event(new UserCreated($user));
            
            return $user;
        });
    }

    public function updateUser(int $id, array $data): bool
    {
        return $this->transaction(function () use ($id, $data) {
            if (isset($data['password'])) {
                $data['password'] = Hash::make($data['password']);
            }
            
            $result = $this->repository->update($id, $data);
            
            if ($result) {
                $user = $this->repository->find($id);
                
                if (isset($data['roles'])) {
                    $user->syncRoles($data['roles']);
                }
                
                if (isset($data['permissions'])) {
                    $user->syncPermissions($data['permissions']);
                }
                
                $this->logActivity('User updated', ['user_id' => $id]);
                
                event(new UserUpdated($user));
            }
            
            return $result;
        });
    }

    public function getUser(int $id)
    {
        return $this->repository->with(['roles', 'permissions'])->find($id);
    }

    public function getAllUsers()
    {
        return $this->repository->with(['roles'])->all();
    }

    public function deleteUser(int $id): bool
    {
        return $this->transaction(function () use ($id) {
            $result = $this->repository->delete($id);
            
            if ($result) {
                $this->logActivity('User deleted', ['user_id' => $id]);
            }
            
            return $result;
        });
    }
}
