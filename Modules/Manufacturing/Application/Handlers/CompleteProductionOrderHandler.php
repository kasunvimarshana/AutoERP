<?php

declare(strict_types=1);

namespace Modules\Manufacturing\Application\Handlers;

use DateTimeImmutable;
use Illuminate\Support\Facades\DB;
use Modules\Inventory\Domain\Contracts\InventoryRepositoryInterface;
use Modules\Inventory\Domain\Entities\StockLedgerEntry;
use Modules\Inventory\Domain\Enums\LedgerEntryType;
use Modules\Manufacturing\Application\Commands\CompleteProductionOrderCommand;
use Modules\Manufacturing\Domain\Contracts\ManufacturingRepositoryInterface;
use Modules\Manufacturing\Domain\Entities\ProductionOrder;

/**
 * Completes a production order and records the matching stock movements:
 *  - MANUFACTURING_CONSUMPTION for each BOM component (deduct from warehouse)
 *  - MANUFACTURING_OUTPUT for the finished goods (add to warehouse)
 *
 * All operations execute within a single DB transaction with pessimistic locks.
 */
class CompleteProductionOrderHandler
{
    public function __construct(
        private readonly ManufacturingRepositoryInterface $manufacturingRepo,
        private readonly InventoryRepositoryInterface     $inventoryRepo,
    ) {}

    /**
     * @throws \DomainException on insufficient stock or invalid state transitions.
     */
    public function handle(CompleteProductionOrderCommand $command): ProductionOrder
    {
        if (bccomp($command->producedQuantity, '0', 4) <= 0) {
            throw new \DomainException('Produced quantity must be greater than zero.');
        }

        return DB::transaction(function () use ($command): ProductionOrder {
            $order = $this->manufacturingRepo->findProductionOrderById(
                $command->orderId,
                $command->tenantId
            );

            if (! $order->getStatus()->canComplete()) {
                throw new \DomainException(
                    'Only in-progress production orders can be completed.'
                );
            }

            $bom = $this->manufacturingRepo->findBomById($order->getBomId(), $command->tenantId);

            // Scale BOM components to the actually produced quantity
            $scaledComponents = $bom->scaledComponents($command->producedQuantity);

            // Deduct each component from inventory (pessimistic lock per component)
            foreach ($scaledComponents as $component) {
                $productId  = (int) $component['component_product_id'];
                $variantId  = isset($component['component_variant_id'])
                    ? (int) $component['component_variant_id']
                    : null;
                $qty        = $component['required_quantity'];
                $warehouseId = $order->getWarehouseId();

                $available = $this->inventoryRepo->getStockLevelForUpdate(
                    $productId,
                    $warehouseId,
                    $command->tenantId
                );

                if (bccomp($available, $qty, 4) < 0) {
                    throw new \DomainException(
                        "Insufficient stock for component product ID {$productId}. "
                        . "Available: {$available}, required: {$qty}."
                    );
                }

                $this->inventoryRepo->recordEntry(new StockLedgerEntry(
                    id: 0,
                    tenantId: $command->tenantId,
                    productId: $productId,
                    variantId: $variantId,
                    warehouseId: $warehouseId,
                    type: LedgerEntryType::MANUFACTURING_CONSUMPTION,
                    quantity: bcadd($qty, '0', 4),
                    unitCost: '0.0000',
                    referenceType: 'production_order',
                    referenceId: $order->getId(),
                    notes: "Component consumed for production order #{$order->getReferenceNo()}",
                    createdAt: new DateTimeImmutable(),
                ));
            }

            // Add finished goods to inventory
            $this->inventoryRepo->recordEntry(new StockLedgerEntry(
                id: 0,
                tenantId: $command->tenantId,
                productId: $order->getProductId(),
                variantId: $order->getVariantId(),
                warehouseId: $order->getWarehouseId(),
                type: LedgerEntryType::MANUFACTURING_OUTPUT,
                quantity: bcadd($command->producedQuantity, '0', 4),
                unitCost: '0.0000',
                referenceType: 'production_order',
                referenceId: $order->getId(),
                notes: "Finished goods from production order #{$order->getReferenceNo()}",
                createdAt: new DateTimeImmutable(),
            ));

            return $this->manufacturingRepo->updateProductionOrderCompletion(
                $command->orderId,
                $command->tenantId,
                $command->producedQuantity
            );
        });
    }
}
