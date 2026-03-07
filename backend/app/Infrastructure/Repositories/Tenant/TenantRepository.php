<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories\Tenant;

use App\Domain\Tenant\Contracts\TenantRepositoryInterface;
use App\Infrastructure\Repositories\BaseRepository;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Eloquent implementation of the Tenant repository.
 */
class TenantRepository extends BaseRepository implements TenantRepositoryInterface
{
    protected array $filterable = ['status', 'plan'];
    protected array $searchable = ['name', 'slug', 'domain'];

    public function __construct(Tenant $model)
    {
        parent::__construct($model);
    }

    /**
     * Tenant repository bypasses the standard tenant scope
     * (tenants are not scoped by tenant_id).
     */
    protected function applyTenantScope(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query;
    }

    public function findBySlug(string $slug): ?Model
    {
        return $this->model->newQuery()->where('slug', $slug)->first();
    }

    public function findByDomain(string $domain): ?Model
    {
        return $this->model->newQuery()->where('domain', $domain)->first();
    }

    public function findActive(): Collection
    {
        return $this->model->newQuery()->where('status', 'active')->get();
    }

    public function updateConfig(int|string $tenantId, array $config): Model
    {
        return DB::transaction(function () use ($tenantId, $config): Model {
            /** @var Tenant $tenant */
            $tenant = $this->findOrFail($tenantId);

            $merged = array_merge($tenant->config ?? [], $config);
            $tenant->update(['config' => $merged]);

            return $tenant->fresh();
        });
    }
}
