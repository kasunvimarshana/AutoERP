<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

/**
 * WimsModuleServiceProvider
 *
 * Master service provider that bootstraps all WIMS modules.
 * Each module registers its own ServiceProvider, which handles:
 *   - Migrations
 *   - Routes
 *   - Config
 *   - Events/Observers
 *   - Interface bindings
 *
 * Registration order matters (respect FK dependencies):
 *   1. Catalog          (products, types, attributes)
 *   2. UnitOfMeasure    (UOM, conversions, product UOM settings)
 *   3. Warehouse        (warehouses, locations, putaway, replenishment)
 *   4. Inventory        (settings, lots, serials, stock_levels, layers)
 *   5. StockMovement    (operation_types, movements, lines, scrap)
 *   6. Procurement      (POs, receipts, landed costs)
 *   7. Sales            (SOs, deliveries, picks, packs)
 *   8. Returns          (RMA, RTV, quality inspection)
 *   9. Allocation       (reservations, rules, wave)
 *  10. CycleCounting    (ABC, plans, sessions, counts, discrepancies)
 *  11. Valuation        (costing assignments, revaluations, COGS, periods)
 *  12. Audit            (audit trails, ledger, alerts, access logs)
 */
class WimsModuleServiceProvider extends ServiceProvider
{
    /**
     * Module service providers in dependency order.
     */
    protected array $moduleProviders = [
        \App\Modules\Catalog\Providers\CatalogServiceProvider::class,
        \App\Modules\UnitOfMeasure\Providers\UnitOfMeasureServiceProvider::class,
        \App\Modules\Warehouse\Providers\WarehouseServiceProvider::class,
        \App\Modules\Inventory\Providers\InventoryServiceProvider::class,
        \App\Modules\StockMovement\Providers\StockMovementServiceProvider::class,
        \App\Modules\Procurement\Providers\ProcurementServiceProvider::class,
        \App\Modules\Sales\Providers\SalesServiceProvider::class,
        \App\Modules\Returns\Providers\ReturnsServiceProvider::class,
        \App\Modules\Allocation\Providers\AllocationServiceProvider::class,
        \App\Modules\CycleCounting\Providers\CycleCountingServiceProvider::class,
        \App\Modules\Valuation\Providers\ValuationServiceProvider::class,
        \App\Modules\Audit\Providers\AuditServiceProvider::class,
    ];

    public function register(): void
    {
        $enabledModules = config('wims.modules', []);

        foreach ($this->moduleProviders as $provider) {
            // Derive module name from provider namespace
            $moduleName = $this->extractModuleName($provider);

            if (! isset($enabledModules[$moduleName]) || $enabledModules[$moduleName]) {
                $this->app->register($provider);
            }
        }
    }

    public function boot(): void
    {
        // Publish all module configs
        $this->publishes([
            __DIR__ . '/../../config/wims.php' => config_path('wims.php'),
        ], 'wims-config');
    }

    protected function extractModuleName(string $providerClass): string
    {
        // e.g. App\Modules\Inventory\Providers\InventoryServiceProvider → Inventory
        preg_match('/Modules\\\\([^\\\\]+)\\\\/', $providerClass, $matches);
        return $matches[1] ?? '';
    }
}
