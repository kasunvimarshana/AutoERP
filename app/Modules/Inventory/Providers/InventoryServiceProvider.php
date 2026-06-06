<?php

declare(strict_types=1);

namespace Modules\Inventory\Providers;

use Illuminate\Support\ServiceProvider;

final class InventoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../Routes/api.php');
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
    }
}
