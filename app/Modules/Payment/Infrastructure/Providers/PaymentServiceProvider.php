<?php

declare(strict_types=1);

namespace Modules\Payment\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Payment\Application\Services\PaymentAllocationService;
use Modules\Payment\Application\Services\PaymentService;

final class PaymentServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../Config/payment.php', 'payment');
        $this->app->singleton(PaymentAllocationService::class);
        $this->app->singleton(PaymentService::class);
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../../routes/api.php');
        $this->loadMigrationsFrom(__DIR__.'/../Persistence/Eloquent/Migrations');
    }
}
