<?php

declare(strict_types=1);

namespace Modules\UOM\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;


final class UomServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../Config/uom.php', 'uom');
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../Persistence/Eloquent/Migrations');
    }
}
