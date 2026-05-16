<?php

declare(strict_types=1);

namespace Modules\Core\Infrastructure\Concerns;

use Illuminate\Support\Facades\Route;

trait LoadsModuleRoutesAndMigrations
{
    protected function bootModule(
        string $routesPath,
        string $migrationsPath,
        string $prefix = 'api',
        string|array $middleware = 'api'
    ): void {
        Route::middleware($middleware)
            ->prefix($prefix)
            ->group(function () use ($routesPath): void {
                $this->loadRoutesFrom($routesPath);
            });

        $this->loadMigrationsFrom($migrationsPath);
    }
}
