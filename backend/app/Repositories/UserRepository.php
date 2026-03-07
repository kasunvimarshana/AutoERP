<?php

namespace App\Repositories;

use App\Models\User;
use Illuminate\Container\Container as App;

class UserRepository extends BaseRepository
{
    protected array $searchableColumns = ['name', 'email', 'phone'];

    protected function model(): string
    {
        return User::class;
    }

    public function findByEmail(string $email): ?User
    {
        return $this->newQuery()->where('email', $email)->first();
    }

    public function findByEmailAndTenant(string $email, int $tenantId): ?User
    {
        return $this->model->withoutGlobalScope('tenant')
                           ->where('email', $email)
                           ->where('tenant_id', $tenantId)
                           ->first();
    }

    public function getActiveUsers(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->newQuery()->where('status', 'active')->get();
    }

    public function getUsersByRole(string $role): \Illuminate\Database\Eloquent\Collection
    {
        return $this->newQuery()
                    ->whereHas('roles', fn ($q) => $q->where('name', $role))
                    ->get();
    }
}
