<?php

declare(strict_types=1);

namespace Modules\Billing\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Modules\Billing\Models\Plan;
use Modules\Billing\Models\Subscription;
use Modules\Billing\Models\SubscriptionPayment;
use Modules\Billing\Policies\PlanPolicy;
use Modules\Billing\Policies\SubscriptionPaymentPolicy;
use Modules\Billing\Policies\SubscriptionPolicy;

class BillingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Register module configuration
        $this->mergeConfigFrom(__DIR__.'/../Config/billing.php', 'billing');

        // Register repositories
        $this->app->singleton(\Modules\Billing\Repositories\PlanRepository::class);
        $this->app->singleton(\Modules\Billing\Repositories\SubscriptionRepository::class);
        $this->app->singleton(\Modules\Billing\Repositories\SubscriptionPaymentRepository::class);

        // Register services
        $this->app->singleton(\Modules\Billing\Services\BillingCalculationService::class);
        $this->app->singleton(\Modules\Billing\Services\SubscriptionService::class);
        $this->app->singleton(\Modules\Billing\Services\PaymentService::class);
        $this->app->singleton(\Modules\Billing\Services\UsageTrackingService::class);
    }

    public function boot(): void
    {
        // Load migrations
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');

        // Load routes
        $this->loadRoutesFrom(__DIR__.'/../routes/api.php');

        // Register policies
        Gate::policy(Plan::class, PlanPolicy::class);
        Gate::policy(Subscription::class, SubscriptionPolicy::class);
        Gate::policy(SubscriptionPayment::class, SubscriptionPaymentPolicy::class);
    }
}
