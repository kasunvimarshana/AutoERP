<?php

declare(strict_types=1);

namespace Modules\Finance\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Core\Contracts\PermissionDefinitionRegistryInterface;
use Modules\Finance\Constants\FinancePermission;
use Modules\Finance\Contracts\FinancePaymentReversalInterface;
use Modules\Finance\Contracts\FinancePostingInterface;
use Modules\Finance\Contracts\FinanceSourceReversalInterface;
use Modules\Finance\Services\FinancePostingService;
use Modules\Finance\Services\ReversalService;

final class FinanceServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(FinancePostingInterface::class, FinancePostingService::class);
        $this->app->singleton(ReversalService::class);
        $this->app->alias(ReversalService::class, FinancePaymentReversalInterface::class);
        $this->app->alias(ReversalService::class, FinanceSourceReversalInterface::class);
    }

    public function boot(): void
    {
        $this->app->make(PermissionDefinitionRegistryInterface::class)
            ->register('finance', FinancePermission::descriptions());

        $this->loadRoutesFrom(__DIR__.'/../Routes/api.php');
        $this->loadMigrationsFrom([
            __DIR__.'/../Database/Migrations',
            __DIR__.'/../Database/UpgradeMigrations',
        ]);
    }
}
