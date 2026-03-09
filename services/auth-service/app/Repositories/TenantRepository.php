<?php

namespace App\Repositories;

use App\Domain\Contracts\TenantRepositoryInterface;
use App\Domain\Models\Tenant;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class TenantRepository extends BaseRepository implements TenantRepositoryInterface
{
    protected function getModelClass(): string
    {
        return Tenant::class;
    }

    protected function getDefaultSearchFields(): array
    {
        return ['name', 'subdomain'];
    }

    protected function getFilterableColumns(): array
    {
        return ['id', 'name', 'subdomain', 'plan', 'status', 'created_at', 'updated_at'];
    }

    public function findBySubdomain(string $subdomain): ?Tenant
    {
        return $this->newQuery()
                    ->where('subdomain', strtolower(trim($subdomain)))
                    ->first();
    }

    public function findAll(array $params = []): LengthAwarePaginator|Collection
    {
        return parent::findAll($params);
    }

    public function findActive(): Collection
    {
        return $this->newQuery()->where('status', 'active')->get();
    }

    protected function beforeCreate(array $data): array
    {
        if (isset($data['subdomain'])) {
            $data['subdomain'] = strtolower(trim($data['subdomain']));
        }
        return $data;
    }
}
