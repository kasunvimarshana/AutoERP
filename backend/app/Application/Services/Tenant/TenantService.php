<?php

declare(strict_types=1);

namespace App\Application\Services\Tenant;

use App\Application\DTOs\TenantDTO;
use App\Domain\Tenant\Contracts\TenantRepositoryInterface;
use App\Domain\Tenant\Events\TenantCreated;
use App\Infrastructure\MessageBroker\Contracts\MessageBrokerInterface;
use App\Infrastructure\MultiTenant\TenantManager;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Application service for Tenant management.
 */
final class TenantService
{
    public function __construct(
        private readonly TenantRepositoryInterface $tenantRepository,
        private readonly MessageBrokerInterface $messageBroker,
        private readonly TenantManager $tenantManager,
    ) {}

    public function list(array $filters = []): mixed
    {
        return $this->tenantRepository->all($filters);
    }

    public function get(int|string $id): Model
    {
        return $this->tenantRepository->findOrFail($id);
    }

    public function getBySlug(string $slug): ?Model
    {
        return $this->tenantRepository->findBySlug($slug);
    }

    /**
     * Provision a new tenant.
     */
    public function create(TenantDTO $dto): Model
    {
        return DB::transaction(function () use ($dto): Model {
            $tenant = $this->tenantRepository->create($dto->toArray());

            event(new TenantCreated($tenant, Auth::id() ?? 0));

            $this->messageBroker->publish('tenant.created', [
                'tenant_id' => $tenant->id,
                'slug'      => $tenant->slug,
                'plan'      => $tenant->plan,
            ]);

            Log::info("[TenantService] Tenant '{$tenant->slug}' provisioned (id={$tenant->id}).");

            return $tenant;
        });
    }

    /**
     * Update tenant settings.
     */
    public function update(int|string $id, array $attributes): Model
    {
        return DB::transaction(fn () => $this->tenantRepository->update($id, $attributes));
    }

    /**
     * Update runtime configuration for a tenant.
     */
    public function updateConfig(int|string $tenantId, array $config): Model
    {
        return DB::transaction(function () use ($tenantId, $config): Model {
            $tenant = $this->tenantRepository->updateConfig($tenantId, $config);

            // Flush tenant cache so the new config is picked up immediately.
            $this->tenantManager->flushTenantCache($tenantId);

            return $tenant;
        });
    }

    /**
     * Delete (deactivate) a tenant.
     */
    public function delete(int|string $id): bool
    {
        return DB::transaction(fn () => $this->tenantRepository->delete($id));
    }

    /**
     * Return all active tenants.
     */
    public function getActive(): Collection
    {
        return $this->tenantRepository->findActive();
    }

    /**
     * Switch the active tenant context for the current request.
     */
    public function switchContext(int|string $tenantId): void
    {
        $tenant = $this->tenantRepository->findOrFail($tenantId);
        $this->tenantManager->setCurrentTenant($tenant);
    }
}
