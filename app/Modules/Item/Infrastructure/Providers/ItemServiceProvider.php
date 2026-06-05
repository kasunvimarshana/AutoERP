<?php

declare(strict_types=1);

namespace Modules\Item\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;

final class ItemServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../Config/item.php', 'item');
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../../Infrastructure/Persistence/Eloquent/Migrations');
    }
}
