<?php

namespace App\Providers;

use App\Contracts\Pricing\PricingEngineInterface;
use App\Contracts\Services\AuthServiceInterface;
use App\Contracts\Services\InventoryServiceInterface;
use App\Contracts\Services\InvoiceServiceInterface;
use App\Contracts\Services\OrderServiceInterface;
use App\Contracts\Services\ProductServiceInterface;
use App\Contracts\Services\TenantServiceInterface;
use App\Contracts\Services\WorkflowEngineInterface;
use App\Listeners\AuditEventSubscriber;
use App\Listeners\WebhookEventSubscriber;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\Product;
use App\Policies\InvoicePolicy;
use App\Policies\OrderPolicy;
use App\Policies\ProductPolicy;
use App\Services\AuthService;
use App\Services\InventoryService;
use App\Services\InvoiceService;
use App\Services\OrderService;
use App\Services\PricingEngine;
use App\Services\ProductService;
use App\Services\TenantService;
use App\Services\WorkflowEngineService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;

class AppServiceProvider extends AuthServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Product::class => ProductPolicy::class,
        Order::class => OrderPolicy::class,
        Invoice::class => InvoicePolicy::class,
    ];

    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(PricingEngineInterface::class, PricingEngine::class);
        $this->app->bind(AuthServiceInterface::class, AuthService::class);
        $this->app->bind(TenantServiceInterface::class, TenantService::class);
        $this->app->bind(ProductServiceInterface::class, ProductService::class);
        $this->app->bind(OrderServiceInterface::class, OrderService::class);
        $this->app->bind(InventoryServiceInterface::class, InventoryService::class);
        $this->app->bind(InvoiceServiceInterface::class, InvoiceService::class);
        $this->app->bind(WorkflowEngineInterface::class, WorkflowEngineService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        // Register webhook event subscriber
        Event::subscribe(WebhookEventSubscriber::class);

        // Register audit event subscriber for automatic domain-event audit trail
        Event::subscribe(AuditEventSubscriber::class);

        // General API rate limit: 120 req/min per user (or IP for guests)
        RateLimiter::for('api', function (Request $request) {
            return $request->user()
                ? Limit::perMinute((int) env('API_RATE_LIMIT', 120))->by($request->user()->id)
                : Limit::perMinute(30)->by($request->ip());
        });

        // Stricter limit on authentication endpoints to prevent brute-force
        RateLimiter::for('auth', function (Request $request) {
            return Limit::perMinute(10)->by($request->ip());
        });
    }
}
