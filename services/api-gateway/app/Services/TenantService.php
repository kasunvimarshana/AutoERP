<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\Repositories\TenantRepositoryInterface;
use App\Contracts\Services\TenantServiceInterface;
use App\Domain\Tenant\Models\Tenant;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

/**
 * Tenant Service
 *
 * Handles tenant lifecycle management, context isolation,
 * and runtime configuration without requiring app restart.
 */
class TenantService implements TenantServiceInterface
{
    /**
     * Current tenant context.
     */
    private ?Tenant $currentTenant = null;

    public function __construct(
        private readonly TenantRepositoryInterface $tenantRepository,
        private readonly LoggerInterface $logger
    ) {}

    /**
     * {@inheritdoc}
     */
    public function register(array $data): Tenant
    {
        $data['id'] = (string) Str::uuid();
        $data['slug'] = Str::slug($data['name']);

        return DB::transaction(function () use ($data) {
            $tenant = $this->tenantRepository->create($data);

            $this->logger->info('New tenant registered', [
                'tenant_id' => $tenant->id,
                'name' => $tenant->name,
            ]);

            return $tenant;
        });
    }

    /**
     * {@inheritdoc}
     */
    public function current(): ?Tenant
    {
        return $this->currentTenant;
    }

    /**
     * {@inheritdoc}
     */
    public function setContext(Tenant $tenant): void
    {
        $this->currentTenant = $tenant;
    }

    /**
     * {@inheritdoc}
     *
     * Applies tenant-specific database connections, cache drivers,
     * mail configurations, etc. at runtime without restart.
     */
    public function applyRuntimeConfig(Tenant $tenant): void
    {
        $config = $this->tenantRepository->getConfiguration($tenant->id);

        foreach ($config as $key => $value) {
            $allowedKeys = config('messaging.allowed_runtime_keys', [
                'cache.default',
                'database.default',
                'mail.default',
                'queue.default',
                'session.driver',
            ]);

            if (in_array($key, $allowedKeys, true)) {
                Config::set($key, $value);
            }
        }

        $this->logger->debug('Tenant runtime config applied', [
            'tenant_id' => $tenant->id,
            'config_keys' => array_keys($config),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function updateConfig(string $tenantId, string $group, array $config): bool
    {
        $result = $this->tenantRepository->updateConfiguration($tenantId, $config);

        $this->logger->info('Tenant config updated', [
            'tenant_id' => $tenantId,
            'group' => $group,
            'keys' => array_keys($config),
        ]);

        return $result;
    }
}
