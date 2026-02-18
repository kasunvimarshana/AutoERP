<?php

namespace App\Modules\Manufacturing\Services;

use App\Enums\StockMovementType;
use App\Modules\Inventory\DTOs\StockMovementDTO;
use App\Modules\Inventory\Services\StockMovementService;
use App\Modules\Manufacturing\DTOs\CreateProductionOrderDTO;
use App\Modules\Manufacturing\DTOs\MaterialConsumptionDTO;
use App\Modules\Manufacturing\Enums\ProductionOrderStatus;
use App\Modules\Manufacturing\Events\ProductionOrderCompleted;
use App\Modules\Manufacturing\Events\ProductionOrderCreated;
use App\Modules\Manufacturing\Models\ProductionOrder;
use App\Modules\Manufacturing\Repositories\BillOfMaterialRepository;
use App\Modules\Manufacturing\Repositories\ProductionOrderRepository;
use App\Services\BaseService;
use Illuminate\Support\Facades\Auth;

/**
 * Production Order Service
 *
 * Handles business logic for production order lifecycle including
 * creation, releasing, material consumption, and completion.
 */
class ProductionOrderService extends BaseService
{
    public function __construct(
        protected ProductionOrderRepository $productionOrderRepository,
        protected BillOfMaterialRepository $bomRepository,
        protected StockMovementService $stockMovementService
    ) {}

    /**
     * Get all production orders with pagination
     */
    public function getAllProductionOrders(int $perPage = 15)
    {
        return $this->productionOrderRepository->paginate($perPage);
    }

    /**
     * Create a new production order
     */
    public function createProductionOrder(CreateProductionOrderDTO $dto): ProductionOrder
    {
        return $this->transaction(function () use ($dto) {
            $this->logInfo('Creating new production order', ['po_number' => $dto->productionOrderNumber]);

            // Get BOM if not provided
            $bomId = $dto->billOfMaterialId;
            if (! $bomId) {
                $bom = $this->bomRepository->getLatestActiveForProduct($dto->productId);
                if ($bom) {
                    $bomId = $bom->id;
                }
            }

            // Create production order
            $productionOrder = $this->productionOrderRepository->create([
                'production_order_number' => $dto->productionOrderNumber,
                'product_id' => $dto->productId,
                'bill_of_material_id' => $bomId,
                'quantity' => $dto->quantity,
                'warehouse_id' => $dto->warehouseId,
                'scheduled_start_date' => $dto->scheduledStartDate,
                'scheduled_end_date' => $dto->scheduledEndDate,
                'status' => $dto->status?->value ?? ProductionOrderStatus::DRAFT->value,
                'priority' => $dto->priority?->value ?? 'normal',
                'notes' => $dto->notes,
                'created_by' => Auth::id(),
            ]);

            // Create production order items from BOM
            if ($bomId) {
                $bom = $this->bomRepository->findWithItems($bomId);
                foreach ($bom->items as $bomItem) {
                    $plannedQuantity = $bomItem->getActualQuantityNeeded($dto->quantity);

                    $productionOrder->items()->create([
                        'product_id' => $bomItem->component_product_id,
                        'planned_quantity' => $plannedQuantity,
                        'consumed_quantity' => 0,
                        'uom_id' => $bomItem->uom_id,
                    ]);
                }
            }

            // Load relationships
            $productionOrder->load([
                'product',
                'billOfMaterial.items.componentProduct',
                'warehouse',
                'items.product',
            ]);

            // Dispatch event
            event(new ProductionOrderCreated($productionOrder));

            $this->logInfo('Production order created successfully', ['id' => $productionOrder->id]);

            return $productionOrder;
        });
    }

    /**
     * Update a production order
     */
    public function updateProductionOrder(int $id, CreateProductionOrderDTO $dto): ProductionOrder
    {
        return $this->transaction(function () use ($id, $dto) {
            $productionOrder = $this->productionOrderRepository->findOrFail($id);

            // Check if can be edited
            if (! $productionOrder->status->canEdit()) {
                throw new \InvalidArgumentException(
                    "Production order with status '{$productionOrder->status->label()}' cannot be edited"
                );
            }

            $this->logInfo('Updating production order', ['id' => $id]);

            // Update production order
            $this->productionOrderRepository->update($id, [
                'production_order_number' => $dto->productionOrderNumber,
                'product_id' => $dto->productId,
                'bill_of_material_id' => $dto->billOfMaterialId,
                'quantity' => $dto->quantity,
                'warehouse_id' => $dto->warehouseId,
                'scheduled_start_date' => $dto->scheduledStartDate,
                'scheduled_end_date' => $dto->scheduledEndDate,
                'priority' => $dto->priority?->value ?? 'normal',
                'notes' => $dto->notes,
            ]);

            $productionOrder->refresh();
            $productionOrder->load([
                'product',
                'billOfMaterial.items.componentProduct',
                'warehouse',
                'items.product',
            ]);

            $this->logInfo('Production order updated successfully', ['id' => $id]);

            return $productionOrder;
        });
    }

    /**
     * Delete a production order
     */
    public function deleteProductionOrder(int $id): bool
    {
        $productionOrder = $this->productionOrderRepository->findOrFail($id);

        // Check if can be deleted
        if (! $productionOrder->status->canEdit()) {
            throw new \InvalidArgumentException(
                "Production order with status '{$productionOrder->status->label()}' cannot be deleted"
            );
        }

        $this->logInfo('Deleting production order', ['id' => $id]);

        $result = $this->productionOrderRepository->delete($id);

        if ($result) {
            $this->logInfo('Production order deleted successfully', ['id' => $id]);
        }

        return $result;
    }

    /**
     * Get a production order by ID
     */
    public function getProductionOrderById(int $id): ProductionOrder
    {
        return $this->productionOrderRepository->findWithRelations($id);
    }

    /**
     * Release a production order (make it ready for production)
     */
    public function release(int $id): ProductionOrder
    {
        return $this->transaction(function () use ($id) {
            $productionOrder = $this->productionOrderRepository->findOrFail($id);

            // Check if can be released
            if (! $productionOrder->status->canRelease()) {
                throw new \InvalidArgumentException(
                    "Production order with status '{$productionOrder->status->label()}' cannot be released"
                );
            }

            $this->logInfo('Releasing production order', ['id' => $id]);

            // Update status
            $this->productionOrderRepository->update($id, [
                'status' => ProductionOrderStatus::RELEASED->value,
                'released_by' => Auth::id(),
                'released_at' => now(),
            ]);

            $productionOrder->refresh();
            $productionOrder->load([
                'product',
                'billOfMaterial.items.componentProduct',
                'warehouse',
                'items.product',
            ]);

            $this->logInfo('Production order released successfully', ['id' => $id]);

            return $productionOrder;
        });
    }

    /**
     * Start production (consume materials)
     */
    public function startProduction(int $id): ProductionOrder
    {
        return $this->transaction(function () use ($id) {
            $productionOrder = $this->productionOrderRepository->findWithRelations($id);

            // Check if can be started
            if (! $productionOrder->status->canStart()) {
                throw new \InvalidArgumentException(
                    "Production order with status '{$productionOrder->status->label()}' cannot be started"
                );
            }

            $this->logInfo('Starting production', ['id' => $id]);

            // Consume materials from inventory
            foreach ($productionOrder->items as $item) {
                if ($item->consumed_quantity < $item->planned_quantity) {
                    $quantityToConsume = $item->planned_quantity - $item->consumed_quantity;

                    // Record stock movement OUT
                    // Note: unitCost is set to 0 as the StockMovementService will automatically
                    // calculate the cost using the FIFO/weighted average method from stock ledger
                    $stockMovementDto = new StockMovementDTO(
                        productId: $item->product_id,
                        movementType: StockMovementType::PRODUCTION_OUT,
                        quantity: $quantityToConsume,
                        unitCost: 0,
                        warehouseId: $productionOrder->warehouse_id,
                        referenceType: ProductionOrder::class,
                        referenceId: $productionOrder->id,
                        notes: "Consumed for production order #{$productionOrder->production_order_number}"
                    );

                    $this->stockMovementService->recordMovement($stockMovementDto);

                    // Update consumed quantity
                    $item->consumed_quantity = $item->planned_quantity;
                    $item->save();
                }
            }

            // Update status
            $this->productionOrderRepository->update($id, [
                'status' => ProductionOrderStatus::IN_PROGRESS->value,
                'actual_start_date' => now(),
            ]);

            $productionOrder->refresh();
            $productionOrder->load([
                'product',
                'billOfMaterial.items.componentProduct',
                'warehouse',
                'items.product',
            ]);

            $this->logInfo('Production started successfully', ['id' => $id]);

            return $productionOrder;
        });
    }

    /**
     * Record material consumption
     */
    public function consumeMaterials(MaterialConsumptionDTO $dto): ProductionOrder
    {
        return $this->transaction(function () use ($dto) {
            $productionOrder = $this->productionOrderRepository->findWithRelations($dto->productionOrderId);

            $this->logInfo('Recording material consumption', ['production_order_id' => $dto->productionOrderId]);

            // Process each consumed item
            foreach ($dto->consumedItems as $consumedItem) {
                $poItem = $productionOrder->items()
                    ->where('product_id', $consumedItem['product_id'])
                    ->first();

                if (! $poItem) {
                    throw new \InvalidArgumentException(
                        "Product {$consumedItem['product_id']} is not in the production order"
                    );
                }

                $quantity = $consumedItem['quantity'];

                // Validate quantity
                if ($quantity <= 0) {
                    throw new \InvalidArgumentException('Consumed quantity must be greater than 0');
                }

                if (($poItem->consumed_quantity + $quantity) > $poItem->planned_quantity) {
                    throw new \InvalidArgumentException(
                        "Consumed quantity exceeds planned quantity for product {$consumedItem['product_id']}"
                    );
                }

                // Record stock movement OUT
                // Note: unitCost is set to 0 as the StockMovementService will automatically
                // calculate the cost using the FIFO/weighted average method from stock ledger
                $stockMovementDto = new StockMovementDTO(
                    productId: $consumedItem['product_id'],
                    movementType: StockMovementType::PRODUCTION_OUT,
                    quantity: $quantity,
                    unitCost: 0,
                    warehouseId: $productionOrder->warehouse_id,
                    referenceType: ProductionOrder::class,
                    referenceId: $productionOrder->id,
                    notes: $dto->notes ?? "Material consumption for PO #{$productionOrder->production_order_number}",
                    batchNumber: $consumedItem['batch_number'] ?? null,
                    lotNumber: $consumedItem['lot_number'] ?? null,
                    serialNumber: $consumedItem['serial_number'] ?? null
                );

                $this->stockMovementService->recordMovement($stockMovementDto);

                // Update consumed quantity
                $poItem->consumed_quantity += $quantity;
                $poItem->save();
            }

            $productionOrder->refresh();

            $this->logInfo('Material consumption recorded', ['production_order_id' => $dto->productionOrderId]);

            return $productionOrder;
        });
    }

    /**
     * Complete production order (add finished goods to inventory)
     */
    public function complete(int $id): ProductionOrder
    {
        return $this->transaction(function () use ($id) {
            $productionOrder = $this->productionOrderRepository->findWithRelations($id);

            // Check if can be completed
            if (! $productionOrder->status->canComplete()) {
                throw new \InvalidArgumentException(
                    "Production order with status '{$productionOrder->status->label()}' cannot be completed"
                );
            }

            $this->logInfo('Completing production order', ['id' => $id]);

            // Add finished goods to inventory
            // Note: unitCost is set to 0. The actual cost of finished goods should ideally
            // be calculated based on consumed materials. For now, the StockMovementService
            // will handle this according to the configured cost calculation method.
            // Future enhancement: Calculate total cost from consumed items and assign here.
            $stockMovementDto = new StockMovementDTO(
                productId: $productionOrder->product_id,
                movementType: StockMovementType::PRODUCTION_IN,
                quantity: $productionOrder->quantity,
                unitCost: 0,
                warehouseId: $productionOrder->warehouse_id,
                referenceType: ProductionOrder::class,
                referenceId: $productionOrder->id,
                notes: "Produced from production order #{$productionOrder->production_order_number}"
            );

            $this->stockMovementService->recordMovement($stockMovementDto);

            // Update status
            $this->productionOrderRepository->update($id, [
                'status' => ProductionOrderStatus::COMPLETED->value,
                'actual_end_date' => now(),
                'completed_by' => Auth::id(),
                'completed_at' => now(),
            ]);

            $productionOrder->refresh();
            $productionOrder->load([
                'product',
                'billOfMaterial.items.componentProduct',
                'warehouse',
                'items.product',
            ]);

            // Dispatch event
            event(new ProductionOrderCompleted($productionOrder));

            $this->logInfo('Production order completed successfully', ['id' => $id]);

            return $productionOrder;
        });
    }

    /**
     * Cancel a production order
     */
    public function cancel(int $id): ProductionOrder
    {
        return $this->transaction(function () use ($id) {
            $productionOrder = $this->productionOrderRepository->findOrFail($id);

            // Check if can be cancelled
            if (! $productionOrder->status->canCancel()) {
                throw new \InvalidArgumentException(
                    "Production order with status '{$productionOrder->status->label()}' cannot be cancelled"
                );
            }

            $this->logInfo('Cancelling production order', ['id' => $id]);

            $this->productionOrderRepository->update($id, [
                'status' => ProductionOrderStatus::CANCELLED->value,
            ]);

            $productionOrder->refresh();
            $productionOrder->load([
                'product',
                'billOfMaterial.items.componentProduct',
                'warehouse',
                'items.product',
            ]);

            $this->logInfo('Production order cancelled successfully', ['id' => $id]);

            return $productionOrder;
        });
    }

    /**
     * Get production orders by status
     */
    public function getByStatus(ProductionOrderStatus $status)
    {
        return $this->productionOrderRepository->getByStatus($status);
    }

    /**
     * Get in-progress production orders
     */
    public function getInProgress()
    {
        return $this->productionOrderRepository->getInProgress();
    }

    /**
     * Search production orders
     */
    public function searchProductionOrders(string $search)
    {
        return $this->productionOrderRepository->search($search);
    }

    /**
     * Get overdue production orders
     */
    public function getOverdue()
    {
        return $this->productionOrderRepository->getOverdue();
    }
}
