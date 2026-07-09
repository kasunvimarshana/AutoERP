<?php

declare(strict_types=1);

namespace Modules\Tax\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Core\Contracts\PermissionDefinitionRegistryInterface;
use Modules\Tax\Constants\TaxPermission;
use Modules\Tax\Contracts\TaxPartyProfileReaderInterface;
use Modules\Tax\Contracts\TaxPartyResolverInterface;
use Modules\Tax\Services\Party\TaxPartyResolverRegistry;
use Modules\Tax\Services\TaxPartyProfileReader;

final class TaxServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../Config/tax.php', 'tax');
        $this->app->singleton(TaxPartyProfileReaderInterface::class, TaxPartyProfileReader::class);
        $this->app->singleton(TaxPartyResolverRegistry::class, static fn ($app): TaxPartyResolverRegistry => new TaxPartyResolverRegistry(
            $app->tagged(TaxPartyResolverInterface::TAG),
        ));
    }

    public function boot(): void
    {
        $this->app->make(PermissionDefinitionRegistryInterface::class)
            ->register('tax', TaxPermission::descriptions());

        $this->loadRoutesFrom(__DIR__.'/../Routes/api.php');
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
    }
}
