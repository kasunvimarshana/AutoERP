<?php

declare(strict_types=1);

namespace Modules\Payment\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Core\Contracts\PermissionDefinitionRegistryInterface;
use Modules\Invoice\Contracts\InvoicePaymentMethodProviderInterface;
use Modules\Payment\Constants\PaymentPermission;
use Modules\Payment\Contracts\PaymentMethodProviderInterface;
use Modules\Payment\Services\InvoicePaymentMethodProvider;
use Modules\Payment\Services\PaymentMethodProvider;

final class PaymentServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(InvoicePaymentMethodProviderInterface::class, InvoicePaymentMethodProvider::class);
        $this->app->singleton(PaymentMethodProviderInterface::class, PaymentMethodProvider::class);
    }

    public function boot(): void
    {
        $this->app->make(PermissionDefinitionRegistryInterface::class)
            ->register('payment', PaymentPermission::descriptions());

        $this->loadRoutesFrom(__DIR__.'/../Routes/api.php');
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
    }
}
