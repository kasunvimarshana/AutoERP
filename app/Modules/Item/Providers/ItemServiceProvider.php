<?php

declare(strict_types=1);

namespace Modules\Item\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Item\Services\ItemAuthorizationService;
use Modules\Core\Contracts\PermissionDefinitionRegistryInterface;

final class ItemServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->app->make(PermissionDefinitionRegistryInterface::class)
            ->register('item', ItemAuthorizationService::descriptions());

        $this->loadRoutesFrom(__DIR__.'/../Routes/api.php');
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
    }
}
