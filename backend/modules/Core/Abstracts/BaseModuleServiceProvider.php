<?php

declare(strict_types=1);

namespace Modules\Core\Abstracts;

use App\Contracts\ModuleContract;
use Illuminate\Support\ServiceProvider;
use Modules\Core\Services\TenantContext;

/**
 * Base Module Service Provider
 *
 * Abstract base class for all module service providers.
 */
abstract class BaseModuleServiceProvider extends ServiceProvider implements ModuleContract
{
    protected string $moduleId;

    protected string $moduleName;

    protected string $moduleVersion = '1.0.0';

    protected array $dependencies = [];

    protected bool $enabled = true;

    public function getModuleId(): string
    {
        return $this->moduleId;
    }

    public function getModuleName(): string
    {
        return $this->moduleName;
    }

    public function getModuleVersion(): string
    {
        return $this->moduleVersion;
    }

    public function getDependencies(): array
    {
        return $this->dependencies;
    }

    public function isEnabled(): bool
    {
        if ($this->app->bound(TenantContext::class)) {
            $tenantContext = $this->app->make(TenantContext::class);
            $tenant = $tenantContext->getTenant();

            if ($tenant && isset($tenant->modules[$this->moduleId])) {
                return (bool) $tenant->modules[$this->moduleId]['enabled'];
            }
        }

        return $this->enabled;
    }

    abstract public function getModuleConfig(): array;

    abstract public function getPermissions(): array;

    abstract public function getRoutes(): array;

    public function getEventListeners(): array
    {
        return [];
    }

    protected function loadModuleRoutes(): void
    {
        $routesPath = $this->getModulePath('routes');

        if (file_exists("{$routesPath}/api.php")) {
            $this->loadRoutesFrom("{$routesPath}/api.php");
        }

        if (file_exists("{$routesPath}/web.php")) {
            $this->loadRoutesFrom("{$routesPath}/web.php");
        }
    }

    protected function loadModuleMigrations(): void
    {
        $migrationsPath = $this->getModulePath('database/migrations');

        if (file_exists($migrationsPath)) {
            $this->loadMigrationsFrom($migrationsPath);
        }
    }

    protected function loadModuleViews(): void
    {
        $viewsPath = $this->getModulePath('resources/views');

        if (file_exists($viewsPath)) {
            $this->loadViewsFrom($viewsPath, strtolower($this->moduleId));
        }
    }

    protected function getModulePath(string $path = ''): string
    {
        $reflection = new \ReflectionClass($this);
        $moduleBasePath = dirname($reflection->getFileName(), 2);

        return $path ? "{$moduleBasePath}/{$path}" : $moduleBasePath;
    }
}
