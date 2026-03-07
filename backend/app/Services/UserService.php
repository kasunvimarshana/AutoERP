<?php

namespace App\Services;

use App\Repositories\UserRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Hash;

class UserService extends BaseService
{
    public function __construct(protected UserRepository $repository)
    {
        parent::__construct($repository);
    }

    public function createUser(array $data): \App\Models\User
    {
        $data['password'] = Hash::make($data['password']);
        $data['status']   = $data['status'] ?? 'active';

        return $this->repository->transaction(function () use ($data) {
            $user = $this->repository->create($data);

            if (!empty($data['role'])) {
                $user->assignRole($data['role']);
            }

            return $user;
        });
    }

    public function updateUser(int $id, array $data): \App\Models\User
    {
        if (!empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        return $this->repository->transaction(function () use ($id, $data) {
            $user = $this->repository->update($id, $data);

            if (!empty($data['role'])) {
                $user->syncRoles([$data['role']]);
            }

            return $user;
        });
    }

    public function deactivateUser(int $id): \App\Models\User
    {
        return $this->repository->update($id, ['status' => 'inactive']);
    }

    public function activateUser(int $id): \App\Models\User
    {
        return $this->repository->update($id, ['status' => 'active']);
    }

    public function getUsersByRole(string $role): Collection
    {
        return $this->repository->getUsersByRole($role);
    }
}
