<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

final class UserRepository implements UserRepositoryInterface
{
    public function findById(int $id): ?User
    {
        return User::find($id);
    }

    public function findByEmail(string $email, ?string $tenantId = null): ?User
    {
        $query = User::where('email', $email);

        if ($tenantId !== null) {
            $query->where('tenant_id', $tenantId);
        }

        return $query->first();
    }

    public function findByTenantId(string $tenantId): Collection
    {
        return User::where('tenant_id', $tenantId)->get();
    }

    public function create(array $data): User
    {
        return User::create($data);
    }

    public function update(int $id, array $data): User
    {
        $user = User::findOrFail($id);
        $user->update($data);
        return $user->fresh();
    }

    public function delete(int $id): bool
    {
        $user = User::findOrFail($id);
        return (bool) $user->delete();
    }

    public function updateLastLogin(int $userId, string $ipAddress): void
    {
        User::where('id', $userId)->update([
            'last_login_at' => now(),
            'last_login_ip' => $ipAddress,
        ]);
    }

    public function incrementTokenVersion(int $userId): void
    {
        User::where('id', $userId)->increment('token_version');
    }
}
