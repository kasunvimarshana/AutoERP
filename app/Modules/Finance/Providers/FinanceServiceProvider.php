<?php

declare(strict_types=1);

namespace Modules\Finance\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Core\Contracts\PermissionDefinitionRegistryInterface;
use Modules\Finance\Constants\FinancePermission;
use Modules\Finance\Contracts\FinancePaymentReversalInterface;
use Modules\Finance\Contracts\FinancePostingInterface;
use Modules\Finance\Services\FinancePostingService;
use Modules\Finance\Services\ReversalService;

final class FinanceServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(FinancePostingInterface::class, FinancePostingService::class);
        $this->app->singleton(FinancePaymentReversalInterface::class, ReversalService::class);
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
