<?php

namespace App\Services;

use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserService
{
    public function paginate(string $tenantId, int $perPage = 15): LengthAwarePaginator
    {
        return User::where('tenant_id', $tenantId)
            ->with(['organization', 'roles'])
            ->orderBy('name')
            ->paginate($perPage);
    }

    public function create(array $data): User
    {
        return DB::transaction(function () use ($data) {
            $roles = $data['roles'] ?? [];
            unset($data['roles']);

            $data['password'] = Hash::make($data['password']);
            $data['status'] ??= UserStatus::Active;

            $user = User::create($data);

            if (! empty($roles)) {
                $user->syncRoles($roles);
            }

            return $user->fresh(['roles']);
        });
    }

    public function update(string $id, array $data): User
    {
        return DB::transaction(function () use ($id, $data) {
            $user = User::findOrFail($id);
            $roles = $data['roles'] ?? null;
            unset($data['roles']);

            if (isset($data['password'])) {
                $data['password'] = Hash::make($data['password']);
            }

            $user->update($data);

            if ($roles !== null) {
                $user->syncRoles($roles);
            }

            return $user->fresh(['roles']);
        });
    }

    public function suspend(string $id): User
    {
        return DB::transaction(function () use ($id) {
            $user = User::findOrFail($id);
            $user->update(['status' => UserStatus::Suspended]);

            return $user->fresh();
        });
    }

    public function activate(string $id): User
    {
        return DB::transaction(function () use ($id) {
            $user = User::findOrFail($id);
            $user->update(['status' => UserStatus::Active]);

            return $user->fresh();
        });
    }
}
