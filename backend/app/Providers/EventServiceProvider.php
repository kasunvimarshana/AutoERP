<?php

namespace App\Providers;

use App\Events\PurchaseOrderApproved;
use App\Events\StockMovementRecorded;
use App\Listeners\CheckReorderLevel;
use App\Listeners\NotifySupplierOfPurchaseOrder;
use App\Modules\Finance\Events\FiscalYearClosed;
use App\Modules\Finance\Events\JournalEntryPosted;
use App\Modules\Finance\Events\JournalEntryVoided;
use App\Modules\Finance\Listeners\NotifyOnFiscalYearClosed;
use App\Modules\Finance\Listeners\RecalculateFinancialStatements;
use App\Modules\Finance\Listeners\UpdateAccountBalances;
use App\Modules\Manufacturing\Events\ProductionOrderCompleted;
use App\Modules\Manufacturing\Events\ProductionOrderCreated as ManufacturingProductionOrderCreated;
use App\Modules\Manufacturing\Listeners\ConsumeInventoryOnProductionStart;
use App\Modules\Manufacturing\Listeners\NotifyOnProductionOrderCompletion;
use App\Modules\Manufacturing\Listeners\ReplenishInventoryOnProductionComplete;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        StockMovementRecorded::class => [
            CheckReorderLevel::class,
        ],
        PurchaseOrderApproved::class => [
            NotifySupplierOfPurchaseOrder::class,
        ],
        ManufacturingProductionOrderCreated::class => [
            ConsumeInventoryOnProductionStart::class,
        ],
        ProductionOrderCompleted::class => [
            ReplenishInventoryOnProductionComplete::class,
            NotifyOnProductionOrderCompletion::class,
        ],
        JournalEntryPosted::class => [
            UpdateAccountBalances::class,
            RecalculateFinancialStatements::class,
        ],
        JournalEntryVoided::class => [
            UpdateAccountBalances::class,
        ],
        FiscalYearClosed::class => [
            NotifyOnFiscalYearClosed::class,
        ],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        //
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
