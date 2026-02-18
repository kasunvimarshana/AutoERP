<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\ModuleContract;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Module Registry Service
 *
 * Central registry for managing all ERP modules.
 * Provides module discovery, dependency resolution, and metadata access.
 */
class ModuleRegistry
{
    protected Collection $modules;

    protected array $moduleMetadata = [];

    protected const CACHE_KEY = 'module_registry';

    protected const CACHE_TTL = 3600;

    public function __construct()
    {
        $this->modules = new Collection;
    }

    public function register(ModuleContract $module): void
    {
        $moduleId = $module->getModuleId();

        if ($this->modules->has($moduleId)) {
            throw new \RuntimeException("Module '{$moduleId}' is already registered");
        }

        $this->modules->put($moduleId, $module);

        $this->moduleMetadata[$moduleId] = [
            'id' => $module->getModuleId(),
            'name' => $module->getModuleName(),
            'version' => $module->getModuleVersion(),
            'dependencies' => $module->getDependencies(),
            'config' => $module->getModuleConfig(),
            'permissions' => $module->getPermissions(),
            'routes' => $module->getRoutes(),
            'enabled' => $module->isEnabled(),
        ];

        // Clear cache if available (not during early bootstrapping)
        $this->safeCacheOperation(function () {
            Cache::forget(self::CACHE_KEY);
        });
    }

    public function get(string $moduleId): ?ModuleContract
    {
        return $this->modules->get($moduleId);
    }

    public function all(): Collection
    {
        return $this->modules;
    }

    public function enabled(): Collection
    {
        return $this->modules->filter(fn (ModuleContract $module) => $module->isEnabled());
    }

    public function has(string $moduleId): bool
    {
        return $this->modules->has($moduleId);
    }

    public function getMetadata(?string $moduleId = null): ?array
    {
        if ($moduleId === null) {
            return $this->getCachedMetadata();
        }

        return $this->moduleMetadata[$moduleId] ?? null;
    }

    public function getCachedMetadata(): array
    {
        return $this->safeCacheOperation(
            fn () => Cache::remember(self::CACHE_KEY, self::CACHE_TTL, fn () => $this->moduleMetadata),
            $this->moduleMetadata
        );
    }

    /**
     * Safely execute cache operation with fallback
     */
    protected function safeCacheOperation(callable $operation, mixed $fallback = null): mixed
    {
        if (! app()->bound('cache')) {
            Log::debug('Cache not bound during module registry operation');

            return $fallback;
        }

        try {
            return $operation();
        } catch (\Illuminate\Contracts\Cache\LockTimeoutException $e) {
            Log::warning('Cache lock timeout in module registry', ['exception' => $e->getMessage()]);

            return $fallback;
        } catch (\RuntimeException $e) {
            Log::warning('Cache runtime error in module registry', ['exception' => $e->getMessage()]);

            return $fallback;
        }
    }

    public function getAllPermissions(): array
    {
        $permissions = [];

        foreach ($this->modules as $module) {
            $permissions = array_merge($permissions, $module->getPermissions());
        }

        return array_unique($permissions);
    }

    public function getAllRoutes(): array
    {
        $routes = [];

        foreach ($this->enabled() as $moduleId => $module) {
            $routes[$moduleId] = $module->getRoutes();
        }

        return $routes;
    }

    public function getStatistics(): array
    {
        return [
            'total' => $this->modules->count(),
            'enabled' => $this->enabled()->count(),
            'disabled' => $this->modules->count() - $this->enabled()->count(),
            'modules' => $this->modules->keys()->toArray(),
        ];
    }
}
