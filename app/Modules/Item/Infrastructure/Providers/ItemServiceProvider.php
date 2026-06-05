<?php

declare(strict_types=1);

namespace Modules\Item\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Item\Application\Services\ItemService;

final class ItemServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../Config/item.php', 'item');
        $this->app->singleton(ItemService::class);
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../../routes/api.php');
        $this->loadMigrationsFrom(__DIR__.'/../Persistence/Eloquent/Migrations');
    }
}
