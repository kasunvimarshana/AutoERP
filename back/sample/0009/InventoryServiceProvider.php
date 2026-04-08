<?php

namespace App\Providers;

use App\Services\DocumentSequenceService;
use App\Services\Inventory\{
    AlertService, AllocationService, InventoryEngine, LedgerService,
    RotationService, ValuationService
};
use App\Services\{
    PhysicalCountService, PurchaseOrderService, SalesOrderService
};
use Illuminate\Support\ServiceProvider;

/**
 * InventoryServiceProvider
 *
 * Registers all inventory system services as singletons.
 * Add to config/app.php providers array:
 *   App\Providers\InventoryServiceProvider::class
 */
class InventoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // ── Foundation ────────────────────────────────────────────────────────
        $this->app->singleton(DocumentSequenceService::class);

        // ── Inventory Core ────────────────────────────────────────────────────
        $this->app->singleton(LedgerService::class);
        $this->app->singleton(ValuationService::class);
        $this->app->singleton(RotationService::class);
        $this->app->singleton(AllocationService::class);
        $this->app->singleton(AlertService::class);

        $this->app->singleton(InventoryEngine::class, function ($app) {
            return new InventoryEngine(
                valuationService:  $app->make(ValuationService::class),
                allocationService: $app->make(AllocationService::class),
                rotationService:   $app->make(RotationService::class),
                ledgerService:     $app->make(LedgerService::class),
            );
        });

        // ── Domain Services ───────────────────────────────────────────────────
        $this->app->singleton(PurchaseOrderService::class, function ($app) {
            return new PurchaseOrderService(
                inventory:  $app->make(InventoryEngine::class),
                sequences:  $app->make(DocumentSequenceService::class),
            );
        });

        $this->app->singleton(SalesOrderService::class, function ($app) {
            return new SalesOrderService(
                inventory:  $app->make(InventoryEngine::class),
                allocation: $app->make(AllocationService::class),
                sequences:  $app->make(DocumentSequenceService::class),
            );
        });

        $this->app->singleton(PhysicalCountService::class, function ($app) {
            return new PhysicalCountService(
                inventory:  $app->make(InventoryEngine::class),
                sequences:  $app->make(DocumentSequenceService::class),
            );
        });
    }

    public function boot(): void
    {
        // ── Scheduled Commands ────────────────────────────────────────────────
        // In App\Console\Kernel:
        // $schedule->command('inventory:scan-expiry')->daily();
        // $schedule->command('inventory:process-reorders')->hourly();
        // $schedule->command('inventory:snapshot')->monthlyOn(1, '00:00');
        // $schedule->command('inventory:classify-abc')->monthly();
    }
}
