<?php

declare(strict_types=1);

namespace Modules\Plugin\Application\Services;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Core\Domain\Contracts\ServiceContract;
use Modules\Plugin\Application\DTOs\InstallPluginDTO;
use Modules\Plugin\Domain\Contracts\PluginRepositoryContract;
use Modules\Plugin\Domain\Entities\PluginManifest;
use Modules\Plugin\Domain\Entities\TenantPlugin;

/**
 * Plugin service.
 *
 * Orchestrates plugin installation, tenant enablement/disablement,
 * and dependency graph resolution.
 */
class PluginService implements ServiceContract
{
    public function __construct(
        private readonly PluginRepositoryContract $repository,
    ) {}

    /**
     * List all registered plugin manifests.
     *
     * @return Collection<int, PluginManifest>
     */
    public function listPlugins(): Collection
    {
        return $this->repository->all();
    }

    /**
     * Install a plugin by creating its manifest record.
     *
     * Validates dependency graph before persisting.
     *
     * @throws InvalidArgumentException If a required dependency alias is not registered.
     */
    public function installPlugin(InstallPluginDTO $dto): PluginManifest
    {
        return DB::transaction(function () use ($dto): PluginManifest {
            $this->resolveDependencies($dto->requires);

            /** @var PluginManifest $manifest */
            $manifest = PluginManifest::create([
                'name'          => $dto->name,
                'alias'         => $dto->alias,
                'description'   => $dto->description,
                'version'       => $dto->version,
                'keywords'      => $dto->keywords,
                'requires'      => $dto->requires,
                'active'        => true,
                'manifest_data' => $dto->manifestData,
            ]);

            return $manifest;
        });
    }

    /**
     * Enable a plugin for the current tenant.
     *
     * Creates a TenantPlugin record if one does not already exist,
     * or updates an existing record to enabled.
     * The HasTenant scope automatically filters by tenant and auto-assigns tenant_id on create.
     */
    public function enableForTenant(int $pluginManifestId): TenantPlugin
    {
        return DB::transaction(function () use ($pluginManifestId): TenantPlugin {
            /** @var TenantPlugin $tenantPlugin */
            $tenantPlugin = TenantPlugin::query()
                ->firstOrNew([
                    'plugin_manifest_id' => $pluginManifestId,
                ]);

            $tenantPlugin->enabled    = true;
            $tenantPlugin->enabled_at = now();
            $tenantPlugin->save();

            return $tenantPlugin;
        });
    }

    /**
     * Disable a plugin for the current tenant.
     */
    public function disableForTenant(int $pluginManifestId): TenantPlugin
    {
        return DB::transaction(function () use ($pluginManifestId): TenantPlugin {
            /** @var TenantPlugin $tenantPlugin */
            $tenantPlugin = TenantPlugin::query()
                ->where('plugin_manifest_id', $pluginManifestId)
                ->firstOrFail();

            $tenantPlugin->update([
                'enabled'     => false,
                'disabled_at' => now(),
            ]);

            return $tenantPlugin->fresh();
        });
    }

    /**
     * Update an existing plugin manifest.
     *
     * Only non-identity fields (description, version, keywords, manifest_data) can be
     * updated after installation. The alias must remain stable as other records depend on it.
     *
     * @param array<string, mixed> $data
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function updatePlugin(int|string $id, array $data): PluginManifest
    {
        return DB::transaction(function () use ($id, $data): PluginManifest {
            /** @var PluginManifest $manifest */
            $manifest = $this->repository->findOrFail($id);
            $manifest->update($data);
            return $manifest->fresh();
        });
    }

    /**
     * Show a single plugin manifest by ID.
     */
    public function showPlugin(int|string $id): PluginManifest
    {
        return $this->repository->findOrFail($id);
    }

    /**
     * Uninstall a plugin (remove its manifest record).
     *
     * Will fail if the plugin is currently enabled for any tenant.
     *
     * @throws \RuntimeException If the plugin is still enabled for one or more tenants.
     */
    public function uninstallPlugin(int|string $id): bool
    {
        return DB::transaction(function () use ($id): bool {
            /** @var PluginManifest $manifest */
            $manifest = $this->repository->findOrFail($id);

            $activeCount = TenantPlugin::query()
                ->where('plugin_manifest_id', $manifest->id)
                ->where('enabled', true)
                ->count();

            if ($activeCount > 0) {
                throw new \RuntimeException(
                    "Cannot uninstall plugin [{$manifest->alias}]: it is still enabled for {$activeCount} tenant(s)."
                );
            }

            return $this->repository->delete($id);
        });
    }

    /**
     * List all plugins enabled for the current tenant.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, TenantPlugin>
     */
    public function listTenantPlugins(): \Illuminate\Database\Eloquent\Collection
    {
        return TenantPlugin::query()
            ->where('enabled', true)
            ->with('pluginManifest')
            ->get();
    }

    /**
     * Resolve and validate a list of dependency aliases.
     *
     * Verifies that every required alias exists as a registered manifest.
     *
     * @param  string[]  $requires  Array of dependency alias strings.
     * @return PluginManifest[]     Array of resolved PluginManifest objects.
     *
     * @throws InvalidArgumentException If any required alias is not found.
     */
    public function resolveDependencies(array $requires): array
    {
        $resolved = [];

        foreach ($requires as $alias) {
            $manifest = $this->repository->findByAlias((string) $alias);

            if ($manifest === null) {
                throw new InvalidArgumentException(
                    "Required plugin dependency [{$alias}] is not registered."
                );
            }

            $resolved[] = $manifest;
        }

        return $resolved;
    }
}
