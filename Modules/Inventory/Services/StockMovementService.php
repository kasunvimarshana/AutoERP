<?php

declare(strict_types=1);

namespace Modules\Inventory\Services;

use Modules\Core\Services\BaseService;
use Modules\Inventory\Entities\StockMovement;
use Modules\Inventory\Events\StockLevelChanged;
use Modules\Inventory\Events\StockMovementRecorded;
use Modules\Inventory\Repositories\Contracts\StockLevelRepositoryInterface;
use Modules\Inventory\Repositories\Contracts\StockMovementRepositoryInterface;

/**
 * Stock Movement Service
 *
 * Handles business logic for stock movements (receipts, shipments, transfers, adjustments).
 */
class StockMovementService extends BaseService
{
    /**
     * StockMovementService constructor.
     */
    public function __construct(
        protected StockMovementRepositoryInterface $stockMovementRepository,
        protected StockLevelRepositoryInterface $stockLevelRepository
    ) {}

    /**
     * Receive stock into warehouse.
     */
    public function receiveStock(array $data): StockMovement
    {
        return $this->executeInTransaction(function () use ($data) {
            $movement = $this->createStockMovement(array_merge($data, [
                'movement_type' => 'receipt',
            ]));

            $this->updateStockLevel(
                $data['product_id'],
                $data['warehouse_id'],
                $data['quantity'],
                'add',
                $data['unit_cost'] ?? null,
                $data['stock_location_id'] ?? null
            );

            event(new StockMovementRecorded($movement));

            $this->logInfo('Stock received', [
                'movement_id' => $movement->id,
                'product_id' => $data['product_id'],
                'quantity' => $data['quantity'],
            ]);

            return $movement;
        });
    }

    /**
     * Ship stock out of warehouse.
     */
    public function shipStock(array $data): StockMovement
    {
        return $this->executeInTransaction(function () use ($data) {
            $movement = $this->createStockMovement(array_merge($data, [
                'movement_type' => 'shipment',
            ]));

            $this->updateStockLevel(
                $data['product_id'],
                $data['warehouse_id'],
                $data['quantity'],
                'remove',
                null,
                $data['stock_location_id'] ?? null
            );

            event(new StockMovementRecorded($movement));

            $this->logInfo('Stock shipped', [
                'movement_id' => $movement->id,
                'product_id' => $data['product_id'],
                'quantity' => $data['quantity'],
            ]);

            return $movement;
        });
    }

    /**
     * Transfer stock between warehouses or locations.
     */
    public function transferStock(array $data): array
    {
        return $this->executeInTransaction(function () use ($data) {
            // Create outbound movement
            $outboundMovement = $this->createStockMovement(array_merge($data, [
                'movement_type' => 'transfer_out',
                'warehouse_id' => $data['from_warehouse_id'],
                'stock_location_id' => $data['from_location_id'] ?? null,
                'to_warehouse_id' => $data['to_warehouse_id'],
                'to_location_id' => $data['to_location_id'] ?? null,
            ]));

            // Create inbound movement
            $inboundMovement = $this->createStockMovement(array_merge($data, [
                'movement_type' => 'transfer_in',
                'warehouse_id' => $data['to_warehouse_id'],
                'stock_location_id' => $data['to_location_id'] ?? null,
                'from_warehouse_id' => $data['from_warehouse_id'],
                'from_location_id' => $data['from_location_id'] ?? null,
            ]));

            // Update stock levels
            $this->updateStockLevel(
                $data['product_id'],
                $data['from_warehouse_id'],
                $data['quantity'],
                'remove',
                null,
                $data['from_location_id'] ?? null
            );

            $this->updateStockLevel(
                $data['product_id'],
                $data['to_warehouse_id'],
                $data['quantity'],
                'add',
                $data['unit_cost'] ?? null,
                $data['to_location_id'] ?? null
            );

            event(new StockMovementRecorded($outboundMovement));
            event(new StockMovementRecorded($inboundMovement));

            $this->logInfo('Stock transferred', [
                'product_id' => $data['product_id'],
                'from_warehouse_id' => $data['from_warehouse_id'],
                'to_warehouse_id' => $data['to_warehouse_id'],
                'quantity' => $data['quantity'],
            ]);

            return [$outboundMovement, $inboundMovement];
        });
    }

    /**
     * Adjust stock levels (inventory count adjustment).
     */
    public function adjustStock(array $data): StockMovement
    {
        return $this->executeInTransaction(function () use ($data) {
            $currentLevel = $this->stockLevelRepository->getByProductAndWarehouse(
                $data['product_id'],
                $data['warehouse_id']
            );

            $currentQuantity = $currentLevel?->quantity_on_hand ?? 0;
            $difference = $data['new_quantity'] - $currentQuantity;

            $movementType = $difference >= 0 ? 'adjustment_in' : 'adjustment_out';
            $quantity = abs($difference);

            $movement = $this->createStockMovement(array_merge($data, [
                'movement_type' => $movementType,
                'quantity' => $quantity,
            ]));

            $this->updateStockLevel(
                $data['product_id'],
                $data['warehouse_id'],
                $quantity,
                $difference >= 0 ? 'add' : 'remove',
                $data['unit_cost'] ?? null,
                $data['stock_location_id'] ?? null
            );

            event(new StockMovementRecorded($movement));

            $this->logInfo('Stock adjusted', [
                'movement_id' => $movement->id,
                'product_id' => $data['product_id'],
                'old_quantity' => $currentQuantity,
                'new_quantity' => $data['new_quantity'],
            ]);

            return $movement;
        });
    }

    /**
     * Get stock movements for a product.
     */
    public function getMovementHistory(int $productId, ?int $limit = null)
    {
        return $this->stockMovementRepository->getByProduct($productId, $limit);
    }

    /**
     * Create a stock movement record.
     */
    protected function createStockMovement(array $data): StockMovement
    {
        if (empty($data['movement_number'])) {
            $data['movement_number'] = $this->generateMovementNumber($data['movement_type']);
        }

        if (empty($data['movement_date'])) {
            $data['movement_date'] = now();
        }

        return $this->stockMovementRepository->create($data);
    }

    /**
     * Update stock level after movement.
     */
    protected function updateStockLevel(
        int $productId,
        int $warehouseId,
        float $quantity,
        string $operation,
        ?float $unitCost = null,
        ?int $locationId = null
    ): void {
        $stockLevel = $locationId
            ? $this->stockLevelRepository->getByProductAndLocation($productId, $locationId)
            : $this->stockLevelRepository->getByProductAndWarehouse($productId, $warehouseId);

        if (! $stockLevel) {
            $stockLevel = $this->stockLevelRepository->create([
                'product_id' => $productId,
                'warehouse_id' => $warehouseId,
                'stock_location_id' => $locationId,
                'quantity_on_hand' => 0,
                'quantity_reserved' => 0,
                'quantity_available' => 0,
                'unit_cost' => $unitCost ?? 0,
                'currency' => 'USD',
            ]);
        }

        if ($operation === 'add') {
            $stockLevel->addQuantity($quantity, $unitCost);
        } else {
            $stockLevel->removeQuantity($quantity);
        }

        event(new StockLevelChanged($stockLevel));
    }

    /**
     * Generate a unique movement number.
     */
    protected function generateMovementNumber(string $movementType): string
    {
        $prefix = match ($movementType) {
            'receipt' => 'RCV',
            'shipment' => 'SHP',
            'transfer_in', 'transfer_out' => 'TRF',
            'adjustment_in', 'adjustment_out' => 'ADJ',
            default => 'MOV',
        };

        $date = date('Ymd');

        $lastMovement = $this->stockMovementRepository
            ->getModel()
            ->where('movement_number', 'LIKE', "{$prefix}-{$date}-%")
            ->orderBy('movement_number', 'desc')
            ->first();

        if ($lastMovement) {
            $parts = explode('-', $lastMovement->movement_number);
            $sequence = (int) end($parts) + 1;
        } else {
            $sequence = 1;
        }

        return sprintf('%s-%s-%04d', $prefix, $date, $sequence);
    }
}
