<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Inventory\Application\Repositories\BatchRepositoryInterface;
use Modules\Inventory\Application\Repositories\CycleCountHeaderRepositoryInterface;
use Modules\Inventory\Application\Repositories\CycleCountLineRepositoryInterface;
use Modules\Inventory\Application\Repositories\InventoryCostLayerRepositoryInterface;
use Modules\Inventory\Application\Repositories\PickingTaskRepositoryInterface;
use Modules\Inventory\Application\Repositories\PutAwayTaskRepositoryInterface;
use Modules\Inventory\Application\Repositories\ReceiptInspectionRepositoryInterface;
use Modules\Inventory\Application\Repositories\SerialRepositoryInterface;
use Modules\Inventory\Application\Repositories\StockAdjustmentLineRepositoryInterface;
use Modules\Inventory\Application\Repositories\StockAdjustmentRepositoryInterface;
use Modules\Inventory\Application\Repositories\StockLevelRepositoryInterface;
use Modules\Inventory\Application\Repositories\StockMovementRepositoryInterface;
use Modules\Inventory\Application\Repositories\StockReservationRepositoryInterface;
use Modules\Inventory\Application\Repositories\StockTransferLineRepositoryInterface;
use Modules\Inventory\Application\Repositories\StockTransferRepositoryInterface;
use Modules\Inventory\Application\Repositories\TraceLogRepositoryInterface;
use Modules\Inventory\Application\Repositories\TransferOrderLineRepositoryInterface;
use Modules\Inventory\Application\Repositories\TransferOrderRepositoryInterface;
use Modules\Inventory\Application\Repositories\ValuationConfigRepositoryInterface;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Repositories\EloquentBatchRepository;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Repositories\EloquentCycleCountHeaderRepository;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Repositories\EloquentCycleCountLineRepository;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Repositories\EloquentInventoryCostLayerRepository;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Repositories\EloquentPickingTaskRepository;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Repositories\EloquentPutAwayTaskRepository;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Repositories\EloquentReceiptInspectionRepository;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Repositories\EloquentSerialRepository;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Repositories\EloquentStockAdjustmentLineRepository;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Repositories\EloquentStockAdjustmentRepository;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Repositories\EloquentStockLevelRepository;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Repositories\EloquentStockMovementRepository;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Repositories\EloquentStockReservationRepository;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Repositories\EloquentStockTransferLineRepository;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Repositories\EloquentStockTransferRepository;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Repositories\EloquentTraceLogRepository;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Repositories\EloquentTransferOrderLineRepository;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Repositories\EloquentTransferOrderRepository;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Repositories\EloquentValuationConfigRepository;

class InventoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        foreach ([
            BatchRepositoryInterface::class => EloquentBatchRepository::class,
            CycleCountHeaderRepositoryInterface::class => EloquentCycleCountHeaderRepository::class,
            CycleCountLineRepositoryInterface::class => EloquentCycleCountLineRepository::class,
            InventoryCostLayerRepositoryInterface::class => EloquentInventoryCostLayerRepository::class,
            PickingTaskRepositoryInterface::class => EloquentPickingTaskRepository::class,
            PutAwayTaskRepositoryInterface::class => EloquentPutAwayTaskRepository::class,
            ReceiptInspectionRepositoryInterface::class => EloquentReceiptInspectionRepository::class,
            SerialRepositoryInterface::class => EloquentSerialRepository::class,
            StockAdjustmentLineRepositoryInterface::class => EloquentStockAdjustmentLineRepository::class,
            StockAdjustmentRepositoryInterface::class => EloquentStockAdjustmentRepository::class,
            StockLevelRepositoryInterface::class => EloquentStockLevelRepository::class,
            StockMovementRepositoryInterface::class => EloquentStockMovementRepository::class,
            StockReservationRepositoryInterface::class => EloquentStockReservationRepository::class,
            StockTransferLineRepositoryInterface::class => EloquentStockTransferLineRepository::class,
            StockTransferRepositoryInterface::class => EloquentStockTransferRepository::class,
            TraceLogRepositoryInterface::class => EloquentTraceLogRepository::class,
            TransferOrderLineRepositoryInterface::class => EloquentTransferOrderLineRepository::class,
            TransferOrderRepositoryInterface::class => EloquentTransferOrderRepository::class,
            ValuationConfigRepositoryInterface::class => EloquentValuationConfigRepository::class,
        ] as $interface => $implementation) {
            $this->app->bind($interface, $implementation);
        }
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../../Infrastructure/Persistence/Eloquent/Migrations');
    }
}
