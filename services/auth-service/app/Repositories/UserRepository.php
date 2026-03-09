<?php

namespace App\Repositories;

use App\Domain\Contracts\UserRepositoryInterface;
use App\Domain\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class UserRepository extends BaseRepository implements UserRepositoryInterface
{
    protected function getModelClass(): string
    {
        return User::class;
    }

    protected function getDefaultSearchFields(): array
    {
        return ['name', 'email', 'phone'];
    }

    protected function getFilterableColumns(): array
    {
        return [
            'id', 'tenant_id', 'org_id', 'name', 'email', 'phone',
            'timezone', 'locale', 'is_active', 'created_at', 'updated_at',
        ];
    }

    /**
     * Find user by email, optionally scoped to a tenant.
     */
    public function findByEmail(string $email, ?string $tenantId = null): ?User
    {
        $query = $this->newQuery()->where('email', strtolower(trim($email)));

        if ($tenantId !== null) {
            $query->where('tenant_id', $tenantId);
        }

        return $query->first();
    }

    /**
     * Find all users.
     */
    public function findAll(array $params = []): LengthAwarePaginator|Collection
    {
        return parent::findAll($params);
    }

    /**
     * Find users for a specific tenant.
     */
    public function findByTenant(string $tenantId, array $params = []): LengthAwarePaginator|Collection
    {
        $params['filters'][] = ['field' => 'tenant_id', 'operator' => '=', 'value' => $tenantId];
        return $this->findAll($params);
    }

    /**
     * Normalize email before create/update.
     */
    protected function beforeCreate(array $data): array
    {
        if (isset($data['email'])) {
            $data['email'] = strtolower(trim($data['email']));
        }
        return $data;
    }

    protected function beforeUpdate(Model $model, array $data): array
    {
        if (isset($data['email'])) {
            $data['email'] = strtolower(trim($data['email']));
        }
        return $data;
    }

    /**
     * Scope to apply tenant isolation by default.
     */
    protected function applyCustomScopes(Builder $query, array $params): Builder
    {
        // If a tenant_id scope is provided and user is not super-admin, enforce it
        if (isset($params['tenant_id'])) {
            $query->where('tenant_id', $params['tenant_id']);
        }

        return $query;
    }
}
