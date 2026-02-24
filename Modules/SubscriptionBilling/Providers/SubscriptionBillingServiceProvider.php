<?php

namespace Modules\SubscriptionBilling\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\SubscriptionBilling\Application\Commands\ProcessSubscriptionRenewalsCommand;
use Modules\SubscriptionBilling\Domain\Contracts\SubscriptionPlanRepositoryInterface;
use Modules\SubscriptionBilling\Domain\Contracts\SubscriptionRepositoryInterface;
use Modules\SubscriptionBilling\Infrastructure\Repositories\SubscriptionPlanRepository;
use Modules\SubscriptionBilling\Infrastructure\Repositories\SubscriptionRepository;

class SubscriptionBillingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(SubscriptionPlanRepositoryInterface::class, SubscriptionPlanRepository::class);
        $this->app->bind(SubscriptionRepositoryInterface::class, SubscriptionRepository::class);
        $this->mergeConfigFrom(__DIR__ . '/../config.php', 'subscription_billing');
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../Infrastructure/Migrations');
        $this->loadRoutesFrom(__DIR__ . '/../routes.php');

        if ($this->app->runningInConsole()) {
            $this->commands([
                ProcessSubscriptionRenewalsCommand::class,
            ]);
        }
    }
}
