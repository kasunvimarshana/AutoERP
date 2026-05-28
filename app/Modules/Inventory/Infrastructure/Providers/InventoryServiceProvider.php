<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Inventory\Application\Contracts\Services\CycleCountServiceInterface;
use Modules\Inventory\Application\Contracts\Services\ReceiptInspectionServiceInterface;
use Modules\Inventory\Application\Contracts\Services\PickingTaskServiceInterface;
use Modules\Inventory\Application\Contracts\Services\PutAwayTaskServiceInterface;
use Modules\Inventory\Application\Contracts\Services\ValuationConfigServiceInterface;
use Modules\Inventory\Application\Contracts\Services\TransferOrderLineServiceInterface;
use Modules\Inventory\Application\Contracts\Services\TransferOrderServiceInterface;
use Modules\Inventory\Application\Contracts\Services\BatchServiceInterface;
use Modules\Inventory\Application\Contracts\Services\SerialServiceInterface;
use Modules\Inventory\Application\Contracts\Services\InventoryCostLayerServiceInterface;
use Modules\Inventory\Application\Contracts\Services\StockMovementServiceInterface;
use Modules\Inventory\Application\Contracts\Services\StockLedgerServiceInterface;
use Modules\Inventory\Application\Contracts\Services\StockAdjustmentServiceInterface;
use Modules\Inventory\Application\Contracts\Services\StockReservationServiceInterface;
use Modules\Inventory\Application\Contracts\Services\StockTransferServiceInterface;
use Modules\Inventory\Application\Contracts\UseCases\Batches\CreateBatchServiceInterface;
use Modules\Inventory\Application\Contracts\UseCases\Batches\DeleteBatchServiceInterface;
use Modules\Inventory\Application\Contracts\UseCases\Batches\GetBatchServiceInterface;
use Modules\Inventory\Application\Contracts\UseCases\InventoryEngines\AllocateInventoryStockServiceInterface;
use Modules\Inventory\Application\Contracts\UseCases\InventoryEngines\CalculateInventoryValuationServiceInterface;
use Modules\Inventory\Application\Contracts\UseCases\InventoryEngines\ResolveInventoryDimensionsServiceInterface;
use Modules\Inventory\Application\Contracts\UseCases\Batches\ListBatchesServiceInterface;
use Modules\Inventory\Application\Contracts\UseCases\Batches\UpdateBatchServiceInterface;
use Modules\Inventory\Application\Contracts\UseCases\CycleCountHeaders\CreateCycleCountHeaderServiceInterface;
use Modules\Inventory\Application\Contracts\UseCases\CycleCountHeaders\DeleteCycleCountHeaderServiceInterface;
use Modules\Inventory\Application\Contracts\UseCases\CycleCountHeaders\GetCycleCountHeaderServiceInterface;
use Modules\Inventory\Application\Contracts\UseCases\CycleCountHeaders\ListCycleCountHeadersServiceInterface;
use Modules\Inventory\Application\Contracts\UseCases\CycleCountHeaders\UpdateCycleCountHeaderServiceInterface;
use Modules\Inventory\Application\Contracts\UseCases\CycleCountLines\CreateCycleCountLineServiceInterface;
use Modules\Inventory\Application\Contracts\UseCases\CycleCountLines\DeleteCycleCountLineServiceInterface;
use Modules\Inventory\Application\Contracts\UseCases\CycleCountLines\GetCycleCountLineServiceInterface;
use Modules\Inventory\Application\Contracts\UseCases\CycleCountLines\ListCycleCountLinesServiceInterface;
use Modules\Inventory\Application\Contracts\UseCases\CycleCountLines\UpdateCycleCountLineServiceInterface;
use Modules\Inventory\Application\Contracts\UseCases\InventoryCostLayers\CreateInventoryCostLayerServiceInterface;
use Modules\Inventory\Application\Contracts\UseCases\InventoryCostLayers\DeleteInventoryCostLayerServiceInterface;
use Modules\Inventory\Application\Contracts\UseCases\InventoryCostLayers\GetInventoryCostLayerServiceInterface;
use Modules\Inventory\Application\Contracts\UseCases\InventoryCostLayers\ListInventoryCostLayersServiceInterface;
use Modules\Inventory\Application\Contracts\UseCases\InventoryCostLayers\UpdateInventoryCostLayerServiceInterface;
use Modules\Inventory\Application\Contracts\UseCases\PickingTasks\CreatePickingTaskServiceInterface;
use Modules\Inventory\Application\Contracts\UseCases\PickingTasks\DeletePickingTaskServiceInterface;
use Modules\Inventory\Application\Contracts\UseCases\PickingTasks\GetPickingTaskServiceInterface;
use Modules\Inventory\Application\Contracts\UseCases\PickingTasks\ListPickingTasksServiceInterface;
use Modules\Inventory\Application\Contracts\UseCases\PickingTasks\UpdatePickingTaskServiceInterface;
use Modules\Inventory\Application\Contracts\UseCases\PutAwayTasks\CreatePutAwayTaskServiceInterface;
use Modules\Inventory\Application\Contracts\UseCases\PutAwayTasks\DeletePutAwayTaskServiceInterface;
use Modules\Inventory\Application\Contracts\UseCases\PutAwayTasks\GetPutAwayTaskServiceInterface;
use Modules\Inventory\Application\Contracts\UseCases\PutAwayTasks\ListPutAwayTasksServiceInterface;
use Modules\Inventory\Application\Contracts\UseCases\PutAwayTasks\UpdatePutAwayTaskServiceInterface;
use Modules\Inventory\Application\Contracts\UseCases\ReceiptInspections\CreateReceiptInspectionServiceInterface;
use Modules\Inventory\Application\Contracts\UseCases\ReceiptInspections\DeleteReceiptInspectionServiceInterface;
use Modules\Inventory\Application\Contracts\UseCases\ReceiptInspections\GetReceiptInspectionServiceInterface;
use Modules\Inventory\Application\Contracts\UseCases\ReceiptInspections\ListReceiptInspectionsServiceInterface;
use Modules\Inventory\Application\Contracts\UseCases\ReceiptInspections\UpdateReceiptInspectionServiceInterface;
use Modules\Inventory\Application\Contracts\UseCases\Serials\CreateSerialServiceInterface;
use Modules\Inventory\Application\Contracts\UseCases\Serials\DeleteSerialServiceInterface;
use Modules\Inventory\Application\Contracts\UseCases\Serials\GetSerialServiceInterface;
use Modules\Inventory\Application\Contracts\UseCases\Serials\ListSerialsServiceInterface;
use Modules\Inventory\Application\Contracts\UseCases\Serials\UpdateSerialServiceInterface;
use Modules\Inventory\Application\Contracts\UseCases\StockAdjustmentLines\CreateStockAdjustmentLineServiceInterface;
use Modules\Inventory\Application\Contracts\UseCases\StockAdjustmentLines\DeleteStockAdjustmentLineServiceInterface;
use Modules\Inventory\Application\Contracts\UseCases\StockAdjustmentLines\GetStockAdjustmentLineServiceInterface;
use Modules\Inventory\Application\Contracts\UseCases\StockAdjustmentLines\ListStockAdjustmentLinesServiceInterface;
use Modules\Inventory\Application\Contracts\UseCases\StockAdjustmentLines\UpdateStockAdjustmentLineServiceInterface;
use Modules\Inventory\Application\Contracts\UseCases\StockAdjustments\CreateStockAdjustmentServiceInterface;
use Modules\Inventory\Application\Contracts\UseCases\StockAdjustments\DeleteStockAdjustmentServiceInterface;
use Modules\Inventory\Application\Contracts\UseCases\StockAdjustments\GetStockAdjustmentServiceInterface;
use Modules\Inventory\Application\Contracts\UseCases\StockAdjustments\ListStockAdjustmentsServiceInterface;
use Modules\Inventory\Application\Contracts\UseCases\StockAdjustments\UpdateStockAdjustmentServiceInterface;
use Modules\Inventory\Application\Contracts\UseCases\StockLevels\CreateStockLevelServiceInterface;
use Modules\Inventory\Application\Contracts\UseCases\StockLevels\DeleteStockLevelServiceInterface;
use Modules\Inventory\Application\Contracts\UseCases\StockLevels\GetStockLevelServiceInterface;
use Modules\Inventory\Application\Contracts\UseCases\StockLevels\ListStockLevelsServiceInterface;
use Modules\Inventory\Application\Contracts\UseCases\StockLevels\UpdateStockLevelServiceInterface;
use Modules\Inventory\Application\Contracts\UseCases\StockMovements\CreateStockMovementServiceInterface;
use Modules\Inventory\Application\Contracts\UseCases\StockMovements\DeleteStockMovementServiceInterface;
use Modules\Inventory\Application\Contracts\UseCases\StockMovements\GetStockMovementServiceInterface;
use Modules\Inventory\Application\Contracts\UseCases\StockMovements\ListStockMovementsServiceInterface;
use Modules\Inventory\Application\Contracts\UseCases\StockMovements\UpdateStockMovementServiceInterface;
use Modules\Inventory\Application\Contracts\UseCases\StockReservations\CreateStockReservationServiceInterface;
use Modules\Inventory\Application\Contracts\UseCases\StockReservations\DeleteStockReservationServiceInterface;
use Modules\Inventory\Application\Contracts\UseCases\StockReservations\GetStockReservationServiceInterface;
use Modules\Inventory\Application\Contracts\UseCases\StockReservations\ListStockReservationsServiceInterface;
use Modules\Inventory\Application\Contracts\UseCases\StockReservations\UpdateStockReservationServiceInterface;
use Modules\Inventory\Application\Contracts\UseCases\StockTransferLines\CreateStockTransferLineServiceInterface;
use Modules\Inventory\Application\Contracts\UseCases\StockTransferLines\DeleteStockTransferLineServiceInterface;
use Modules\Inventory\Application\Contracts\UseCases\StockTransferLines\GetStockTransferLineServiceInterface;
use Modules\Inventory\Application\Contracts\UseCases\StockTransferLines\ListStockTransferLinesServiceInterface;
use Modules\Inventory\Application\Contracts\UseCases\StockTransferLines\UpdateStockTransferLineServiceInterface;
use Modules\Inventory\Application\Contracts\UseCases\StockTransfers\CreateStockTransferServiceInterface;
use Modules\Inventory\Application\Contracts\UseCases\StockTransfers\DeleteStockTransferServiceInterface;
use Modules\Inventory\Application\Contracts\UseCases\StockTransfers\GetStockTransferServiceInterface;
use Modules\Inventory\Application\Contracts\UseCases\StockTransfers\ListStockTransfersServiceInterface;
use Modules\Inventory\Application\Contracts\UseCases\StockTransfers\UpdateStockTransferServiceInterface;
use Modules\Inventory\Application\Contracts\UseCases\TraceLogs\CreateTraceLogServiceInterface;
use Modules\Inventory\Application\Contracts\UseCases\TraceLogs\DeleteTraceLogServiceInterface;
use Modules\Inventory\Application\Contracts\UseCases\TraceLogs\GetTraceLogServiceInterface;
use Modules\Inventory\Application\Contracts\UseCases\TraceLogs\ListTraceLogsServiceInterface;
use Modules\Inventory\Application\Contracts\UseCases\TraceLogs\UpdateTraceLogServiceInterface;
use Modules\Inventory\Application\Contracts\UseCases\TransferOrderLines\CreateTransferOrderLineServiceInterface;
use Modules\Inventory\Application\Contracts\UseCases\TransferOrderLines\DeleteTransferOrderLineServiceInterface;
use Modules\Inventory\Application\Contracts\UseCases\TransferOrderLines\GetTransferOrderLineServiceInterface;
use Modules\Inventory\Application\Contracts\UseCases\TransferOrderLines\ListTransferOrderLinesServiceInterface;
use Modules\Inventory\Application\Contracts\UseCases\TransferOrderLines\UpdateTransferOrderLineServiceInterface;
use Modules\Inventory\Application\Contracts\UseCases\TransferOrders\CreateTransferOrderServiceInterface;
use Modules\Inventory\Application\Contracts\UseCases\TransferOrders\DeleteTransferOrderServiceInterface;
use Modules\Inventory\Application\Contracts\UseCases\TransferOrders\GetTransferOrderServiceInterface;
use Modules\Inventory\Application\Contracts\UseCases\TransferOrders\ListTransferOrdersServiceInterface;
use Modules\Inventory\Application\Contracts\UseCases\TransferOrders\UpdateTransferOrderServiceInterface;
use Modules\Inventory\Application\Contracts\UseCases\ValuationConfigs\CreateValuationConfigServiceInterface;
use Modules\Inventory\Application\Contracts\UseCases\ValuationConfigs\DeleteValuationConfigServiceInterface;
use Modules\Inventory\Application\Contracts\UseCases\ValuationConfigs\GetValuationConfigServiceInterface;
use Modules\Inventory\Application\Contracts\UseCases\ValuationConfigs\ListValuationConfigsServiceInterface;
use Modules\Inventory\Application\Contracts\UseCases\ValuationConfigs\UpdateValuationConfigServiceInterface;
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
use Modules\Inventory\Application\UseCases\Batches\CreateBatchService;
use Modules\Inventory\Application\UseCases\Batches\DeleteBatchService;
use Modules\Inventory\Application\UseCases\Batches\GetBatchService;
use Modules\Inventory\Application\UseCases\Batches\ListBatchesService;
use Modules\Inventory\Application\UseCases\Batches\UpdateBatchService;
use Modules\Inventory\Application\UseCases\CycleCountHeaders\CreateCycleCountHeaderService;
use Modules\Inventory\Application\UseCases\CycleCountHeaders\DeleteCycleCountHeaderService;
use Modules\Inventory\Application\UseCases\CycleCountHeaders\GetCycleCountHeaderService;
use Modules\Inventory\Application\UseCases\CycleCountHeaders\ListCycleCountHeadersService;
use Modules\Inventory\Application\UseCases\CycleCountHeaders\UpdateCycleCountHeaderService;
use Modules\Inventory\Application\UseCases\CycleCountLines\CreateCycleCountLineService;
use Modules\Inventory\Application\UseCases\CycleCountLines\DeleteCycleCountLineService;
use Modules\Inventory\Application\UseCases\CycleCountLines\GetCycleCountLineService;
use Modules\Inventory\Application\UseCases\CycleCountLines\ListCycleCountLinesService;
use Modules\Inventory\Application\UseCases\CycleCountLines\UpdateCycleCountLineService;
use Modules\Inventory\Application\UseCases\InventoryCostLayers\CreateInventoryCostLayerService;
use Modules\Inventory\Application\UseCases\InventoryCostLayers\DeleteInventoryCostLayerService;
use Modules\Inventory\Application\UseCases\InventoryCostLayers\GetInventoryCostLayerService;
use Modules\Inventory\Application\UseCases\InventoryCostLayers\ListInventoryCostLayersService;
use Modules\Inventory\Application\UseCases\InventoryCostLayers\UpdateInventoryCostLayerService;
use Modules\Inventory\Application\Services\CycleCountService;
use Modules\Inventory\Application\Services\ReceiptInspectionService;
use Modules\Inventory\Application\Services\PickingTaskService;
use Modules\Inventory\Application\Services\PutAwayTaskService;
use Modules\Inventory\Application\Services\ValuationConfigService;
use Modules\Inventory\Application\Services\TransferOrderLineService;
use Modules\Inventory\Application\Services\TransferOrderService;
use Modules\Inventory\Application\Services\BatchService;
use Modules\Inventory\Application\Services\SerialService;
use Modules\Inventory\Application\Services\InventoryCostLayerService;
use Modules\Inventory\Application\Services\StockMovementService;
use Modules\Inventory\Application\Services\StockLedgerService;
use Modules\Inventory\Application\Services\StockAdjustmentService;
use Modules\Inventory\Application\Services\StockReservationService;
use Modules\Inventory\Application\Services\StockTransferService;
use Modules\Inventory\Application\UseCases\InventoryEngines\AllocateInventoryStockService;
use Modules\Inventory\Application\UseCases\InventoryEngines\CalculateInventoryValuationService;
use Modules\Inventory\Application\UseCases\InventoryEngines\ResolveInventoryDimensionsService;
use Modules\Inventory\Application\UseCases\PickingTasks\CreatePickingTaskService;
use Modules\Inventory\Application\UseCases\PickingTasks\DeletePickingTaskService;
use Modules\Inventory\Application\UseCases\PickingTasks\GetPickingTaskService;
use Modules\Inventory\Application\UseCases\PickingTasks\ListPickingTasksService;
use Modules\Inventory\Application\UseCases\PickingTasks\UpdatePickingTaskService;
use Modules\Inventory\Application\UseCases\PutAwayTasks\CreatePutAwayTaskService;
use Modules\Inventory\Application\UseCases\PutAwayTasks\DeletePutAwayTaskService;
use Modules\Inventory\Application\UseCases\PutAwayTasks\GetPutAwayTaskService;
use Modules\Inventory\Application\UseCases\PutAwayTasks\ListPutAwayTasksService;
use Modules\Inventory\Application\UseCases\PutAwayTasks\UpdatePutAwayTaskService;
use Modules\Inventory\Application\UseCases\ReceiptInspections\CreateReceiptInspectionService;
use Modules\Inventory\Application\UseCases\ReceiptInspections\DeleteReceiptInspectionService;
use Modules\Inventory\Application\UseCases\ReceiptInspections\GetReceiptInspectionService;
use Modules\Inventory\Application\UseCases\ReceiptInspections\ListReceiptInspectionsService;
use Modules\Inventory\Application\UseCases\ReceiptInspections\UpdateReceiptInspectionService;
use Modules\Inventory\Application\UseCases\Serials\CreateSerialService;
use Modules\Inventory\Application\UseCases\Serials\DeleteSerialService;
use Modules\Inventory\Application\UseCases\Serials\GetSerialService;
use Modules\Inventory\Application\UseCases\Serials\ListSerialsService;
use Modules\Inventory\Application\UseCases\Serials\UpdateSerialService;
use Modules\Inventory\Application\UseCases\StockAdjustmentLines\CreateStockAdjustmentLineService;
use Modules\Inventory\Application\UseCases\StockAdjustmentLines\DeleteStockAdjustmentLineService;
use Modules\Inventory\Application\UseCases\StockAdjustmentLines\GetStockAdjustmentLineService;
use Modules\Inventory\Application\UseCases\StockAdjustmentLines\ListStockAdjustmentLinesService;
use Modules\Inventory\Application\UseCases\StockAdjustmentLines\UpdateStockAdjustmentLineService;
use Modules\Inventory\Application\UseCases\StockAdjustments\CreateStockAdjustmentService;
use Modules\Inventory\Application\UseCases\StockAdjustments\DeleteStockAdjustmentService;
use Modules\Inventory\Application\UseCases\StockAdjustments\GetStockAdjustmentService;
use Modules\Inventory\Application\UseCases\StockAdjustments\ListStockAdjustmentsService;
use Modules\Inventory\Application\UseCases\StockAdjustments\UpdateStockAdjustmentService;
use Modules\Inventory\Application\UseCases\StockLevels\CreateStockLevelService;
use Modules\Inventory\Application\UseCases\StockLevels\DeleteStockLevelService;
use Modules\Inventory\Application\UseCases\StockLevels\GetStockLevelService;
use Modules\Inventory\Application\UseCases\StockLevels\ListStockLevelsService;
use Modules\Inventory\Application\UseCases\StockLevels\UpdateStockLevelService;
use Modules\Inventory\Application\UseCases\StockMovements\CreateStockMovementService;
use Modules\Inventory\Application\UseCases\StockMovements\DeleteStockMovementService;
use Modules\Inventory\Application\UseCases\StockMovements\GetStockMovementService;
use Modules\Inventory\Application\UseCases\StockMovements\ListStockMovementsService;
use Modules\Inventory\Application\UseCases\StockMovements\UpdateStockMovementService;
use Modules\Inventory\Application\UseCases\StockReservations\CreateStockReservationService;
use Modules\Inventory\Application\UseCases\StockReservations\DeleteStockReservationService;
use Modules\Inventory\Application\UseCases\StockReservations\GetStockReservationService;
use Modules\Inventory\Application\UseCases\StockReservations\ListStockReservationsService;
use Modules\Inventory\Application\UseCases\StockReservations\UpdateStockReservationService;
use Modules\Inventory\Application\UseCases\StockTransferLines\CreateStockTransferLineService;
use Modules\Inventory\Application\UseCases\StockTransferLines\DeleteStockTransferLineService;
use Modules\Inventory\Application\UseCases\StockTransferLines\GetStockTransferLineService;
use Modules\Inventory\Application\UseCases\StockTransferLines\ListStockTransferLinesService;
use Modules\Inventory\Application\UseCases\StockTransferLines\UpdateStockTransferLineService;
use Modules\Inventory\Application\UseCases\StockTransfers\CreateStockTransferService;
use Modules\Inventory\Application\UseCases\StockTransfers\DeleteStockTransferService;
use Modules\Inventory\Application\UseCases\StockTransfers\GetStockTransferService;
use Modules\Inventory\Application\UseCases\StockTransfers\ListStockTransfersService;
use Modules\Inventory\Application\UseCases\StockTransfers\UpdateStockTransferService;
use Modules\Inventory\Application\UseCases\TraceLogs\CreateTraceLogService;
use Modules\Inventory\Application\UseCases\TraceLogs\DeleteTraceLogService;
use Modules\Inventory\Application\UseCases\TraceLogs\GetTraceLogService;
use Modules\Inventory\Application\UseCases\TraceLogs\ListTraceLogsService;
use Modules\Inventory\Application\UseCases\TraceLogs\UpdateTraceLogService;
use Modules\Inventory\Application\UseCases\TransferOrderLines\CreateTransferOrderLineService;
use Modules\Inventory\Application\UseCases\TransferOrderLines\DeleteTransferOrderLineService;
use Modules\Inventory\Application\UseCases\TransferOrderLines\GetTransferOrderLineService;
use Modules\Inventory\Application\UseCases\TransferOrderLines\ListTransferOrderLinesService;
use Modules\Inventory\Application\UseCases\TransferOrderLines\UpdateTransferOrderLineService;
use Modules\Inventory\Application\UseCases\TransferOrders\CreateTransferOrderService;
use Modules\Inventory\Application\UseCases\TransferOrders\DeleteTransferOrderService;
use Modules\Inventory\Application\UseCases\TransferOrders\GetTransferOrderService;
use Modules\Inventory\Application\UseCases\TransferOrders\ListTransferOrdersService;
use Modules\Inventory\Application\UseCases\TransferOrders\UpdateTransferOrderService;
use Modules\Inventory\Application\UseCases\ValuationConfigs\CreateValuationConfigService;
use Modules\Inventory\Application\UseCases\ValuationConfigs\DeleteValuationConfigService;
use Modules\Inventory\Application\UseCases\ValuationConfigs\GetValuationConfigService;
use Modules\Inventory\Application\UseCases\ValuationConfigs\ListValuationConfigsService;
use Modules\Inventory\Application\UseCases\ValuationConfigs\UpdateValuationConfigService;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Models\BatchModel;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Models\CycleCountHeaderModel;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Models\CycleCountLineModel;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Models\InventoryCostLayerModel;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Models\PickingTaskModel;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Models\PutAwayTaskModel;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Models\ReceiptInspectionModel;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Models\SerialModel;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Models\StockAdjustmentLineModel;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Models\StockAdjustmentModel;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Models\StockLevelModel;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Models\StockMovementModel;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Models\StockReservationModel;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Models\StockTransferLineModel;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Models\StockTransferModel;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Models\TraceLogModel;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Models\TransferOrderLineModel;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Models\TransferOrderModel;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Models\ValuationConfigModel;
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

final class InventoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../Config/inventory.php', 'inventory');

        foreach (
            [
                ListBatchesServiceInterface::class => ListBatchesService::class,
                GetBatchServiceInterface::class => GetBatchService::class,
                CreateBatchServiceInterface::class => CreateBatchService::class,
                UpdateBatchServiceInterface::class => UpdateBatchService::class,
                DeleteBatchServiceInterface::class => DeleteBatchService::class,
                ListSerialsServiceInterface::class => ListSerialsService::class,
                GetSerialServiceInterface::class => GetSerialService::class,
                CreateSerialServiceInterface::class => CreateSerialService::class,
                UpdateSerialServiceInterface::class => UpdateSerialService::class,
                DeleteSerialServiceInterface::class => DeleteSerialService::class,
                ListValuationConfigsServiceInterface::class => ListValuationConfigsService::class,
                GetValuationConfigServiceInterface::class => GetValuationConfigService::class,
                CreateValuationConfigServiceInterface::class => CreateValuationConfigService::class,
                UpdateValuationConfigServiceInterface::class => UpdateValuationConfigService::class,
                DeleteValuationConfigServiceInterface::class => DeleteValuationConfigService::class,
                ListStockLevelsServiceInterface::class => ListStockLevelsService::class,
                GetStockLevelServiceInterface::class => GetStockLevelService::class,
                CreateStockLevelServiceInterface::class => CreateStockLevelService::class,
                UpdateStockLevelServiceInterface::class => UpdateStockLevelService::class,
                DeleteStockLevelServiceInterface::class => DeleteStockLevelService::class,
                ListStockMovementsServiceInterface::class => ListStockMovementsService::class,
                GetStockMovementServiceInterface::class => GetStockMovementService::class,
                CreateStockMovementServiceInterface::class => CreateStockMovementService::class,
                UpdateStockMovementServiceInterface::class => UpdateStockMovementService::class,
                DeleteStockMovementServiceInterface::class => DeleteStockMovementService::class,
                ListInventoryCostLayersServiceInterface::class => ListInventoryCostLayersService::class,
                GetInventoryCostLayerServiceInterface::class => GetInventoryCostLayerService::class,
                CreateInventoryCostLayerServiceInterface::class => CreateInventoryCostLayerService::class,
                UpdateInventoryCostLayerServiceInterface::class => UpdateInventoryCostLayerService::class,
                DeleteInventoryCostLayerServiceInterface::class => DeleteInventoryCostLayerService::class,
                ListStockReservationsServiceInterface::class => ListStockReservationsService::class,
                GetStockReservationServiceInterface::class => GetStockReservationService::class,
                CreateStockReservationServiceInterface::class => CreateStockReservationService::class,
                UpdateStockReservationServiceInterface::class => UpdateStockReservationService::class,
                DeleteStockReservationServiceInterface::class => DeleteStockReservationService::class,
                ListStockTransfersServiceInterface::class => ListStockTransfersService::class,
                GetStockTransferServiceInterface::class => GetStockTransferService::class,
                CreateStockTransferServiceInterface::class => CreateStockTransferService::class,
                UpdateStockTransferServiceInterface::class => UpdateStockTransferService::class,
                DeleteStockTransferServiceInterface::class => DeleteStockTransferService::class,
                ListStockTransferLinesServiceInterface::class => ListStockTransferLinesService::class,
                GetStockTransferLineServiceInterface::class => GetStockTransferLineService::class,
                CreateStockTransferLineServiceInterface::class => CreateStockTransferLineService::class,
                UpdateStockTransferLineServiceInterface::class => UpdateStockTransferLineService::class,
                DeleteStockTransferLineServiceInterface::class => DeleteStockTransferLineService::class,
                ListStockAdjustmentsServiceInterface::class => ListStockAdjustmentsService::class,
                GetStockAdjustmentServiceInterface::class => GetStockAdjustmentService::class,
                CreateStockAdjustmentServiceInterface::class => CreateStockAdjustmentService::class,
                UpdateStockAdjustmentServiceInterface::class => UpdateStockAdjustmentService::class,
                DeleteStockAdjustmentServiceInterface::class => DeleteStockAdjustmentService::class,
                ListStockAdjustmentLinesServiceInterface::class => ListStockAdjustmentLinesService::class,
                GetStockAdjustmentLineServiceInterface::class => GetStockAdjustmentLineService::class,
                CreateStockAdjustmentLineServiceInterface::class => CreateStockAdjustmentLineService::class,
                UpdateStockAdjustmentLineServiceInterface::class => UpdateStockAdjustmentLineService::class,
                DeleteStockAdjustmentLineServiceInterface::class => DeleteStockAdjustmentLineService::class,
                ListCycleCountHeadersServiceInterface::class => ListCycleCountHeadersService::class,
                GetCycleCountHeaderServiceInterface::class => GetCycleCountHeaderService::class,
                CreateCycleCountHeaderServiceInterface::class => CreateCycleCountHeaderService::class,
                UpdateCycleCountHeaderServiceInterface::class => UpdateCycleCountHeaderService::class,
                DeleteCycleCountHeaderServiceInterface::class => DeleteCycleCountHeaderService::class,
                ListCycleCountLinesServiceInterface::class => ListCycleCountLinesService::class,
                GetCycleCountLineServiceInterface::class => GetCycleCountLineService::class,
                CreateCycleCountLineServiceInterface::class => CreateCycleCountLineService::class,
                UpdateCycleCountLineServiceInterface::class => UpdateCycleCountLineService::class,
                DeleteCycleCountLineServiceInterface::class => DeleteCycleCountLineService::class,
                ListTransferOrdersServiceInterface::class => ListTransferOrdersService::class,
                GetTransferOrderServiceInterface::class => GetTransferOrderService::class,
                CreateTransferOrderServiceInterface::class => CreateTransferOrderService::class,
                UpdateTransferOrderServiceInterface::class => UpdateTransferOrderService::class,
                DeleteTransferOrderServiceInterface::class => DeleteTransferOrderService::class,
                ListTransferOrderLinesServiceInterface::class => ListTransferOrderLinesService::class,
                GetTransferOrderLineServiceInterface::class => GetTransferOrderLineService::class,
                CreateTransferOrderLineServiceInterface::class => CreateTransferOrderLineService::class,
                UpdateTransferOrderLineServiceInterface::class => UpdateTransferOrderLineService::class,
                DeleteTransferOrderLineServiceInterface::class => DeleteTransferOrderLineService::class,
                ListTraceLogsServiceInterface::class => ListTraceLogsService::class,
                GetTraceLogServiceInterface::class => GetTraceLogService::class,
                CreateTraceLogServiceInterface::class => CreateTraceLogService::class,
                UpdateTraceLogServiceInterface::class => UpdateTraceLogService::class,
                DeleteTraceLogServiceInterface::class => DeleteTraceLogService::class,
                ListReceiptInspectionsServiceInterface::class => ListReceiptInspectionsService::class,
                GetReceiptInspectionServiceInterface::class => GetReceiptInspectionService::class,
                CreateReceiptInspectionServiceInterface::class => CreateReceiptInspectionService::class,
                UpdateReceiptInspectionServiceInterface::class => UpdateReceiptInspectionService::class,
                DeleteReceiptInspectionServiceInterface::class => DeleteReceiptInspectionService::class,
                ListPutAwayTasksServiceInterface::class => ListPutAwayTasksService::class,
                GetPutAwayTaskServiceInterface::class => GetPutAwayTaskService::class,
                CreatePutAwayTaskServiceInterface::class => CreatePutAwayTaskService::class,
                UpdatePutAwayTaskServiceInterface::class => UpdatePutAwayTaskService::class,
                DeletePutAwayTaskServiceInterface::class => DeletePutAwayTaskService::class,
                ListPickingTasksServiceInterface::class => ListPickingTasksService::class,
                GetPickingTaskServiceInterface::class => GetPickingTaskService::class,
                CreatePickingTaskServiceInterface::class => CreatePickingTaskService::class,
                UpdatePickingTaskServiceInterface::class => UpdatePickingTaskService::class,
                DeletePickingTaskServiceInterface::class => DeletePickingTaskService::class,
                CycleCountServiceInterface::class => CycleCountService::class,
                ReceiptInspectionServiceInterface::class => ReceiptInspectionService::class,
                PickingTaskServiceInterface::class => PickingTaskService::class,
                PutAwayTaskServiceInterface::class => PutAwayTaskService::class,
                ValuationConfigServiceInterface::class => ValuationConfigService::class,
                TransferOrderServiceInterface::class => TransferOrderService::class,
                TransferOrderLineServiceInterface::class => TransferOrderLineService::class,
                BatchServiceInterface::class => BatchService::class,
                SerialServiceInterface::class => SerialService::class,
                InventoryCostLayerServiceInterface::class => InventoryCostLayerService::class,
                StockMovementServiceInterface::class => StockMovementService::class,
                StockLedgerServiceInterface::class => StockLedgerService::class,
                StockAdjustmentServiceInterface::class => StockAdjustmentService::class,
                StockReservationServiceInterface::class => StockReservationService::class,
                StockTransferServiceInterface::class => StockTransferService::class,
                ResolveInventoryDimensionsServiceInterface::class => ResolveInventoryDimensionsService::class,
                CalculateInventoryValuationServiceInterface::class => CalculateInventoryValuationService::class,
                AllocateInventoryStockServiceInterface::class => AllocateInventoryStockService::class,
            ] as $contract => $implementation
        ) {
            $this->app->singleton($contract, $implementation);
        }

        $this->app->singleton(BatchRepositoryInterface::class, function (): BatchRepositoryInterface {
            return new EloquentBatchRepository(new BatchModel());
        });
        $this->app->singleton(SerialRepositoryInterface::class, function (): SerialRepositoryInterface {
            return new EloquentSerialRepository(
                new SerialModel(),
            );
        });
        $this->app->singleton(
            ValuationConfigRepositoryInterface::class,
            function (): ValuationConfigRepositoryInterface {
                return new EloquentValuationConfigRepository(new ValuationConfigModel());
            },
        );
        $this->app->singleton(StockLevelRepositoryInterface::class, function (): StockLevelRepositoryInterface {
            return new EloquentStockLevelRepository(new StockLevelModel());
        });
        $this->app->singleton(StockMovementRepositoryInterface::class, function (): StockMovementRepositoryInterface {
            return new EloquentStockMovementRepository(
                new StockMovementModel(),
            );
        });
        $this->app->singleton(
            InventoryCostLayerRepositoryInterface::class,
            function (): InventoryCostLayerRepositoryInterface {
                return new EloquentInventoryCostLayerRepository(
                    new InventoryCostLayerModel(),
                );
            },
        );
        $this->app->singleton(
            StockReservationRepositoryInterface::class,
            function (): StockReservationRepositoryInterface {
                return new EloquentStockReservationRepository(new StockReservationModel());
            },
        );
        $this->app->singleton(StockTransferRepositoryInterface::class, function (): StockTransferRepositoryInterface {
            return new EloquentStockTransferRepository(
                new StockTransferModel(),
            );
        });
        $this->app->singleton(
            StockTransferLineRepositoryInterface::class,
            function (): StockTransferLineRepositoryInterface {
                return new EloquentStockTransferLineRepository(
                    new StockTransferLineModel(),
                );
            },
        );
        $this->app->singleton(
            StockAdjustmentRepositoryInterface::class,
            function (): StockAdjustmentRepositoryInterface {
                return new EloquentStockAdjustmentRepository(
                    new StockAdjustmentModel(),
                );
            },
        );
        $this->app->singleton(
            StockAdjustmentLineRepositoryInterface::class,
            function (): StockAdjustmentLineRepositoryInterface {
                return new EloquentStockAdjustmentLineRepository(
                    new StockAdjustmentLineModel(),
                );
            },
        );
        $this->app->singleton(
            CycleCountHeaderRepositoryInterface::class,
            function (): CycleCountHeaderRepositoryInterface {
                return new EloquentCycleCountHeaderRepository(new CycleCountHeaderModel());
            },
        );
        $this->app->singleton(CycleCountLineRepositoryInterface::class, function (): CycleCountLineRepositoryInterface {
            return new EloquentCycleCountLineRepository(new CycleCountLineModel());
        });
        $this->app->singleton(TransferOrderRepositoryInterface::class, function (): TransferOrderRepositoryInterface {
            return new EloquentTransferOrderRepository(
                new TransferOrderModel(),
            );
        });
        $this->app->singleton(
            TransferOrderLineRepositoryInterface::class,
            function (): TransferOrderLineRepositoryInterface {
                return new EloquentTransferOrderLineRepository(new TransferOrderLineModel());
            },
        );
        $this->app->singleton(TraceLogRepositoryInterface::class, function (): TraceLogRepositoryInterface {
            return new EloquentTraceLogRepository(
                new TraceLogModel(),
            );
        });
        $this->app->singleton(
            ReceiptInspectionRepositoryInterface::class,
            function (): ReceiptInspectionRepositoryInterface {
                return new EloquentReceiptInspectionRepository(new ReceiptInspectionModel());
            },
        );
        $this->app->singleton(PutAwayTaskRepositoryInterface::class, function (): PutAwayTaskRepositoryInterface {
            return new EloquentPutAwayTaskRepository(new PutAwayTaskModel());
        });
        $this->app->singleton(PickingTaskRepositoryInterface::class, function (): PickingTaskRepositoryInterface {
            return new EloquentPickingTaskRepository(new PickingTaskModel());
        });
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../../routes/api.php');
        $this->loadMigrationsFrom(__DIR__ . '/../Persistence/Eloquent/Migrations');
    }
}
