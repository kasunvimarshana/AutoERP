<?php

declare(strict_types=1);

namespace Modules\Hr\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Core\Contracts\PermissionDefinitionRegistryInterface;
use Modules\Hr\Services\HrAuthorizationService;

final class HrServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->app->make(PermissionDefinitionRegistryInterface::class)
            ->register('hr', HrAuthorizationService::descriptions());

        $this->loadRoutesFrom(__DIR__.'/../Routes/api.php');
        $this->loadMigrationsFrom([
            __DIR__.'/../Database/Migrations',
            __DIR__.'/../Database/UpgradeMigrations',
        ]);
    }
}
