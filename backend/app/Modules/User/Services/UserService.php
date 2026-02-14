<?php

namespace App\Modules\User\Services;

use App\Base\BaseService;
use App\Modules\User\Repositories\UserRepository;
use App\Modules\User\DTOs\UserDTO;
use App\Modules\User\Events\UserCreated;
use App\Modules\User\Events\UserUpdated;
use App\Modules\User\Events\UserDeleted;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Exception;

class UserService extends BaseService
{
    /**
     * UserService constructor.
     *
     * @param UserRepository $repository
     */
    public function __construct(UserRepository $repository)
    {
        parent::__construct($repository);
    }

    /**
     * Create a new user
     */
    public function createUser(UserDTO $dto): Model
    {
        return $this->executeInTransaction(function () use ($dto) {
            $data = $dto->toArray();
            
            if (isset($data['password'])) {
                $data['password'] = Hash::make($data['password']);
            }
            
            $user = $this->repository->create($data);
            
            if ($dto->role) {
                $user->assignRole($dto->role);
            }
            
            if (!empty($dto->permissions)) {
                $user->syncPermissions($dto->permissions);
            }

            Event::dispatch(new UserCreated($user));
            
            return $user;
        });
    }

    /**
     * Update user
     */
    public function updateUser(string $id, UserDTO $dto): bool
    {
        return $this->executeInTransaction(function () use ($id, $dto) {
            $user = $this->repository->find($id);
            
            if (!$user) {
                return false;
            }
            
            $data = $dto->toArray();
            
            if (isset($data['password'])) {
                $data['password'] = Hash::make($data['password']);
            }
            
            $result = $this->repository->update($id, $data);
            
            if ($result) {
                if ($dto->role) {
                    $user->syncRoles([$dto->role]);
                }
                
                if (!empty($dto->permissions)) {
                    $user->syncPermissions($dto->permissions);
                }
                
                $user->refresh();
                Event::dispatch(new UserUpdated($user));
            }
            
            return $result;
        });
    }

    /**
     * Delete user
     */
    public function deleteUser(string $id): bool
    {
        return $this->executeInTransaction(function () use ($id) {
            $user = $this->repository->find($id);
            
            if (!$user) {
                return false;
            }
            
            $result = $this->repository->delete($id);
            
            if ($result) {
                Event::dispatch(new UserDeleted($user));
            }
            
            return $result;
        });
    }

    /**
     * Assign role to user
     */
    public function assignRole(string $userId, string $role): bool
    {
        return $this->executeInTransaction(function () use ($userId, $role) {
            $user = $this->repository->find($userId);
            
            if (!$user) {
                return false;
            }
            
            $user->assignRole($role);
            
            return true;
        });
    }

    /**
     * Remove role from user
     */
    public function removeRole(string $userId, string $role): bool
    {
        return $this->executeInTransaction(function () use ($userId, $role) {
            $user = $this->repository->find($userId);
            
            if (!$user) {
                return false;
            }
            
            $user->removeRole($role);
            
            return true;
        });
    }

    /**
     * Sync permissions for user
     */
    public function syncPermissions(string $userId, array $permissions): bool
    {
        return $this->executeInTransaction(function () use ($userId, $permissions) {
            $user = $this->repository->find($userId);
            
            if (!$user) {
                return false;
            }
            
            $user->syncPermissions($permissions);
            
            return true;
        });
    }

    /**
     * Find user by email
     */
    public function findByEmail(string $email): ?Model
    {
        return $this->repository->findByEmail($email);
    }

    /**
     * Find users by tenant
     */
    public function findByTenant(string $tenantId)
    {
        return $this->repository->findByTenant($tenantId);
    }

    /**
     * Get active users
     */
    public function getActiveUsers()
    {
        return $this->repository->getActive();
    }

    /**
     * Activate user
     */
    public function activateUser(string $id): bool
    {
        return $this->executeInTransaction(function () use ($id) {
            return $this->repository->activate($id);
        });
    }

    /**
     * Deactivate user
     */
    public function deactivateUser(string $id): bool
    {
        return $this->executeInTransaction(function () use ($id) {
            return $this->repository->deactivate($id);
        });
    }
}
