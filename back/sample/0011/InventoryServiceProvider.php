<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

// Core
use Modules\Audit\Application\Services\AuditService;

// Inventory engine services
use Modules\Inventory\Application\Services\AlertService;
use Modules\Inventory\Application\Services\AllocationService;
use Modules\Inventory\Application\Services\InventoryEngine;
use Modules\Inventory\Application\Services\LedgerService;
use Modules\Inventory\Application\Services\RotationService;
use Modules\Inventory\Application\Services\SettingsResolver;
use Modules\Inventory\Application\Services\ValuationService;

// Product module
use Modules\Product\Application\ServiceInterfaces\CreateProductServiceInterface;
use Modules\Product\Application\ServiceInterfaces\DeleteProductServiceInterface;
use Modules\Product\Application\ServiceInterfaces\GetProductServiceInterface;
use Modules\Product\Application\ServiceInterfaces\ListProductsServiceInterface;
use Modules\Product\Application\ServiceInterfaces\UpdateProductServiceInterface;
use Modules\Product\Application\Services\CreateProductService;
use Modules\Product\Application\Services\DeleteProductService;
use Modules\Product\Application\Services\GetProductService;
use Modules\Product\Application\Services\ListProductsService;
use Modules\Product\Application\Services\UpdateProductService;
use Modules\Product\Domain\RepositoryInterfaces\ProductRepositoryInterface;
use Modules\Product\Infrastructure\Persistence\Eloquent\Repositories\EloquentProductRepository;

// Warehouse, Procurement, Sales repository interfaces
use Modules\Warehouse\Domain\RepositoryInterfaces\WarehouseRepositoryInterface;
use Modules\Procurement\Domain\RepositoryInterfaces\PurchaseOrderRepositoryInterface;
use Modules\Procurement\Domain\RepositoryInterfaces\SupplierRepositoryInterface;
use Modules\Sales\Domain\RepositoryInterfaces\SalesOrderRepositoryInterface;
use Modules\Sales\Domain\RepositoryInterfaces\CustomerRepositoryInterface;
use Modules\Batch\Domain\RepositoryInterfaces\BatchRepositoryInterface;

/**
 * InventoryServiceProvider
 *
 * Registers all module services and repository bindings.
 * Add to config/app.php providers:
 *   App\Providers\InventoryServiceProvider::class
 *
 * Follows KVAutoERP's confirmed DI pattern:
 *  - No direct new() instantiation in controllers or services
 *  - All dependencies injected through constructor
 *  - Interfaces bound to concrete implementations
 */
final class InventoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // ── Audit ─────────────────────────────────────────────────────────────
        $this->app->singleton(AuditService::class);

        // ── Inventory Core ────────────────────────────────────────────────────
        $this->app->singleton(SettingsResolver::class);
        $this->app->singleton(LedgerService::class);
        $this->app->singleton(ValuationService::class);
        $this->app->singleton(RotationService::class);
        $this->app->singleton(AlertService::class);

        $this->app->singleton(AllocationService::class);

        $this->app->singleton(InventoryEngine::class, function ($app) {
            return new InventoryEngine(
                valuationService:  $app->make(ValuationService::class),
                rotationService:   $app->make(RotationService::class),
                allocationService: $app->make(AllocationService::class),
                ledgerService:     $app->make(LedgerService::class),
                settingsResolver:  $app->make(SettingsResolver::class),
            );
        });

        // ── Product Module ────────────────────────────────────────────────────
        $this->app->singleton(ProductRepositoryInterface::class, EloquentProductRepository::class);

        $this->app->singleton(CreateProductServiceInterface::class, fn ($app) =>
            new CreateProductService($app->make(ProductRepositoryInterface::class))
        );
        $this->app->singleton(UpdateProductServiceInterface::class, fn ($app) =>
            new UpdateProductService($app->make(ProductRepositoryInterface::class))
        );
        $this->app->singleton(DeleteProductServiceInterface::class, fn ($app) =>
            new DeleteProductService($app->make(ProductRepositoryInterface::class))
        );
        $this->app->singleton(GetProductServiceInterface::class, fn ($app) =>
            new GetProductService($app->make(ProductRepositoryInterface::class))
        );
        $this->app->singleton(ListProductsServiceInterface::class, fn ($app) =>
            new ListProductsService($app->make(ProductRepositoryInterface::class))
        );

        // ── Warehouse, Procurement, Sales, Batch ──────────────────────────────
        // Concrete Eloquent repositories for each — same pattern as Product module.
        // Each follows:  Interface → EloquentXxxRepository extends BaseEloquentRepository
        //
        // $this->app->singleton(WarehouseRepositoryInterface::class, EloquentWarehouseRepository::class);
        // $this->app->singleton(PurchaseOrderRepositoryInterface::class, EloquentPurchaseOrderRepository::class);
        // $this->app->singleton(SupplierRepositoryInterface::class, EloquentSupplierRepository::class);
        // $this->app->singleton(SalesOrderRepositoryInterface::class, EloquentSalesOrderRepository::class);
        // $this->app->singleton(CustomerRepositoryInterface::class, EloquentCustomerRepository::class);
        // $this->app->singleton(BatchRepositoryInterface::class, EloquentBatchRepository::class);
        //
        // Application services for each follow the same pattern as Product:
        // CreateXxxService, UpdateXxxService, DeleteXxxService, GetXxxService, ListXxxService
        // each extending BaseService and implementing their ServiceInterface.
    }

    public function boot(): void
    {
        // ── Event → Audit wiring ─────────────────────────────────────────────
        // Domain events are dispatched by Application services.
        // Each listener calls AuditService::record() to persist the audit trail.
        //
        // \Illuminate\Support\Facades\Event::listen(
        //     \Modules\Product\Domain\Events\ProductCreated::class,
        //     \Modules\Product\Infrastructure\Listeners\AuditProductCreatedListener::class,
        // );
        // \Illuminate\Support\Facades\Event::listen(
        //     \Modules\Product\Domain\Events\ProductUpdated::class,
        //     \Modules\Product\Infrastructure\Listeners\AuditProductUpdatedListener::class,
        // );
        // ... (same pattern for all modules)
        //
        // ── Scheduled commands ────────────────────────────────────────────────
        // Registered in app/Console/Kernel.php:
        // $schedule->command('inventory:scan-expiry')->dailyAt('06:00');
        // $schedule->command('inventory:process-reorders')->hourly();
        // $schedule->command('inventory:snapshot')->monthlyOn(1, '00:05');
        // $schedule->command('inventory:classify-abc')->monthlyOn(2, '01:00');
        // $schedule->command('inventory:expire-reservations')->everyFifteenMinutes();
    }
}
