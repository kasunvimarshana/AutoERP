<?php

declare(strict_types=1);

namespace Modules\Core\Services;

use Modules\Core\Contracts\ModuleInterface;

/**
 * BaseModule
 *
 * Abstract base class for all modules in the system
 */
abstract class BaseModule implements ModuleInterface
{
    /**
     * @var array<string, mixed>
     */
    protected array $config = [];

    /**
     * @var array<string>
     */
    protected array $dependencies = [];

    abstract public function getName(): string;

    abstract public function getVersion(): string;

    public function getDependencies(): array
    {
        return $this->dependencies;
    }

    public function isEnabled(): bool
    {
        return app(ModuleRegistry::class)->isEnabled($this->getName());
    }

    public function boot(): void
    {
        // Override in child modules if needed
    }

    public function register(): void
    {
        // Override in child modules if needed
    }

    public function getConfig(): array
    {
        return $this->config;
    }

    /**
     * Load module routes
     */
    protected function loadRoutes(string $path): void
    {
        if (file_exists($path)) {
            require $path;
        }
    }

    /**
     * Load module migrations
     */
    protected function loadMigrations(string $path): void
    {
        if (is_dir($path)) {
            $this->app->afterResolving('migrator', function ($migrator) use ($path) {
                $migrator->path($path);
            });
        }
    }

    /**
     * Load module views
     */
    protected function loadViews(string $name, string $path): void
    {
        if (is_dir($path)) {
            $this->app->make('view')->addNamespace($name, $path);
        }
    }

    /**
     * Publish module configuration
     */
    protected function publishConfig(string $configPath, string $publishPath): void
    {
        $this->app->make('config')->set($publishPath, require $configPath);
    }
}
