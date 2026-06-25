<?php

declare(strict_types=1);

namespace Modules\ReferenceData\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\ReferenceData\Contracts\ReferenceValueLookupInterface;
use Modules\ReferenceData\Services\ReferenceValueLookup;

final class ReferenceDataServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(
            ReferenceValueLookupInterface::class,
            ReferenceValueLookup::class,
        );
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../Routes/api.php');
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
    }
}
