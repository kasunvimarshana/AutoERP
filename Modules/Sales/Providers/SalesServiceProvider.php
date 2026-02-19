<?php

declare(strict_types=1);

namespace Modules\Sales\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Modules\Sales\Models\Invoice;
use Modules\Sales\Models\Order;
use Modules\Sales\Models\Quotation;
use Modules\Sales\Policies\InvoicePolicy;
use Modules\Sales\Policies\OrderPolicy;
use Modules\Sales\Policies\QuotationPolicy;

class SalesServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Register module configuration
        $this->mergeConfigFrom(__DIR__.'/../Config/sales.php', 'sales');

        // Register repositories
        $this->app->singleton(\Modules\Sales\Repositories\QuotationRepository::class);
        $this->app->singleton(\Modules\Sales\Repositories\OrderRepository::class);
        $this->app->singleton(\Modules\Sales\Repositories\InvoiceRepository::class);

        // Register services
        $this->app->singleton(\Modules\Sales\Services\QuotationService::class);
        $this->app->singleton(\Modules\Sales\Services\OrderService::class);
        $this->app->singleton(\Modules\Sales\Services\InvoiceService::class);
    }

    public function boot(): void
    {
        // Load migrations
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');

        // Load routes
        $this->loadRoutesFrom(__DIR__.'/../routes/api.php');

        // Register policies
        Gate::policy(Quotation::class, QuotationPolicy::class);
        Gate::policy(Order::class, OrderPolicy::class);
        Gate::policy(Invoice::class, InvoicePolicy::class);

        // Register event listeners (if Audit module is available)
        if (config('audit.enabled', false)) {
            $this->registerEventListeners();
        }
    }

    /**
     * Register event listeners for audit logging.
     */
    private function registerEventListeners(): void
    {
        // Check if event listeners exist before registering
        if (class_exists(\Modules\Sales\Listeners\LogQuotationCreated::class)) {
            Event::listen(
                \Modules\Sales\Events\QuotationCreated::class,
                \Modules\Sales\Listeners\LogQuotationCreated::class
            );
        }

        if (class_exists(\Modules\Sales\Listeners\LogOrderCreated::class)) {
            Event::listen(
                \Modules\Sales\Events\OrderCreated::class,
                \Modules\Sales\Listeners\LogOrderCreated::class
            );
        }

        if (class_exists(\Modules\Sales\Listeners\LogInvoiceCreated::class)) {
            Event::listen(
                \Modules\Sales\Events\InvoiceCreated::class,
                \Modules\Sales\Listeners\LogInvoiceCreated::class
            );
        }

        if (class_exists(\Modules\Sales\Listeners\LogInvoicePaymentRecorded::class)) {
            Event::listen(
                \Modules\Sales\Events\InvoicePaymentRecorded::class,
                \Modules\Sales\Listeners\LogInvoicePaymentRecorded::class
            );
        }
    }
}
