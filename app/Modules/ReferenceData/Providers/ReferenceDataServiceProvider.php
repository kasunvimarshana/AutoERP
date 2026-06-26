<?php

declare(strict_types=1);

namespace Modules\ReferenceData\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\ReferenceData\Contracts\ReferenceValueLookupInterface;
use Modules\ReferenceData\Services\ReferenceValueLookup;
use Modules\ReferenceData\Contracts\CurrencyDirectoryInterface;
use Modules\ReferenceData\Services\EloquentCurrencyDirectory;
use Modules\ReferenceData\Constants\ReferenceDataPermission;
use Modules\Core\Contracts\PermissionDefinitionRegistryInterface;

final class ReferenceDataServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(
            ReferenceValueLookupInterface::class,
            ReferenceValueLookup::class,
        );
        $this->app->singleton(
            CurrencyDirectoryInterface::class,
            EloquentCurrencyDirectory::class,
        );
    }

    public function boot(): void
    {
        $this->app->make(PermissionDefinitionRegistryInterface::class)
            ->register('reference-data', ReferenceDataPermission::descriptions());

        $this->loadRoutesFrom(__DIR__.'/../Routes/api.php');
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
    }
}
