<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories;

use App\Contracts\Repositories\TenantRepositoryInterface;
use App\Domain\Tenant\Models\Tenant;
use App\Domain\Tenant\Models\TenantConfiguration;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Tenant Repository
 *
 * Handles all tenant data operations including multi-tenant isolation,
 * runtime configuration management, and tenant discovery.
 */
class TenantRepository extends BaseRepository implements TenantRepositoryInterface
{
    protected array $searchableColumns = ['name', 'slug', 'domain'];
    protected array $sortableColumns = ['name', 'created_at', 'updated_at'];
    protected array $filterableColumns = ['status', 'plan'];

    public function __construct(Tenant $model)
    {
        parent::__construct($model);
    }

    /**
     * {@inheritdoc}
     */
    public function findBySlug(string $slug): ?Tenant
    {
        return Cache::remember(
            "tenant:slug:{$slug}",
            now()->addMinutes(15),
            fn () => Tenant::where('slug', $slug)->first()
        );
    }

    /**
     * {@inheritdoc}
     */
    public function findByDomain(string $domain): ?Tenant
    {
        return Cache::remember(
            "tenant:domain:{$domain}",
            now()->addMinutes(15),
            fn () => Tenant::where('domain', $domain)->first()
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getActiveTenants(): Collection
    {
        return Tenant::where('status', 'active')->get();
    }

    /**
     * {@inheritdoc}
     */
    public function updateConfiguration(string $tenantId, array $config): bool
    {
        foreach ($config as $key => $value) {
            TenantConfiguration::updateOrCreate(
                ['tenant_id' => $tenantId, 'config_key' => $key],
                ['config_value' => is_array($value) ? json_encode($value) : $value]
            );
        }

        // Invalidate cache for this tenant's config
        Cache::forget("tenant:config:{$tenantId}");

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getConfiguration(string $tenantId, ?string $group = null): array
    {
        $cacheKey = "tenant:config:{$tenantId}" . ($group ? ":{$group}" : '');

        return Cache::remember($cacheKey, now()->addMinutes(5), function () use ($tenantId, $group) {
            $query = TenantConfiguration::where('tenant_id', $tenantId);

            if ($group) {
                $query->where('config_group', $group);
            }

            return $query->get()
                ->mapWithKeys(fn ($config) => [$config->config_key => $config->config_value])
                ->toArray();
        });
    }

    /**
     * {@inheritdoc}
     * Override to disable tenant scoping for tenant repository itself.
     */
    protected function applyTenantScope(\Illuminate\Database\Eloquent\Builder $query, array $params): void
    {
        // Tenants table does not have a tenant_id column; skip scope
    }
}
