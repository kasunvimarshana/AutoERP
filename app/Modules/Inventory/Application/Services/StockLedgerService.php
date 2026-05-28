<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Services;

use Modules\Core\Application\DTO\DataRecord;
use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Inventory\Application\Contracts\Services\StockLedgerServiceInterface;
use Modules\Inventory\Application\Repositories\BatchRepositoryInterface;
use Modules\Inventory\Application\Repositories\SerialRepositoryInterface;
use Modules\Inventory\Application\Repositories\StockLevelRepositoryInterface;
use Modules\Inventory\Application\Repositories\StockMovementRepositoryInterface;
use Modules\Inventory\Domain\Constants\InventoryErrorCode;
use Modules\Item\Application\Repositories\ItemRepositoryInterface;
use Modules\UOM\Application\Contracts\Services\UomConversionServiceInterface;
use Throwable;

final class StockLedgerService implements StockLedgerServiceInterface
{
    public function __construct(
        private readonly StockMovementRepositoryInterface $stockMovementRepository,
        private readonly StockLevelRepositoryInterface $stockLevelRepository,
        private readonly ItemRepositoryInterface $itemRepository,
        private readonly UomConversionServiceInterface $uomConversionService,
        private readonly BatchRepositoryInterface $batchRepository,
        private readonly SerialRepositoryInterface $serialRepository,
    ) {
    }

    public function recordMovement(array $payload): Result
    {
        try {
            $tenantId = isset($payload['tenant_id']) ? (int) $payload['tenant_id'] : null;
            $itemId = isset($payload['item_id']) ? (int) $payload['item_id'] : null;
            $uomId = isset($payload['uom_id']) ? (int) $payload['uom_id'] : null;
            $warehouseId = isset($payload['warehouse_id']) ? (int) $payload['warehouse_id'] : null;
            $direction = strtoupper(trim((string) ($payload['direction'] ?? '')));
            $quantity = (float) ($payload['quantity'] ?? 0);

            if ($tenantId === null || $itemId === null || $uomId === null || $warehouseId === null) {
                return Result::failure(new Error(
                    InventoryErrorCode::INVALID_VALUE,
                    'tenant_id, item_id, warehouse_id and uom_id are required.',
                ));
            }

            if ($quantity <= 0) {
                return Result::failure(new Error(
                    InventoryErrorCode::INVALID_VALUE,
                    'quantity must be greater than zero.',
                ));
            }

            if (! in_array($direction, ['IN', 'OUT'], true)) {
                return Result::failure(new Error(
                    InventoryErrorCode::INVALID_DIRECTION,
                    'direction must be either IN or OUT.',
                ));
            }

            $item = $this->itemRepository->findByIdInTenant($itemId, $tenantId);
            if ($item === null) {
                return Result::failure(new Error(
                    InventoryErrorCode::ITEM_NOT_FOUND,
                    'Item not found for tenant.',
                ));
            }

            if (! (bool) $item->get('is_stockable', false)) {
                return Result::failure(new Error(
                    InventoryErrorCode::NON_STOCKABLE_ITEM,
                    'Only stockable items can create inventory movements.',
                ));
            }

            $baseUomId = (int) $item->get('base_uom_id');
            $conversion = $this->uomConversionService->convert($quantity, $uomId, $baseUomId, $tenantId, $itemId);
            if ($conversion->isFailure()) {
                return Result::failure(new Error(
                    InventoryErrorCode::UOM_CONVERSION_FAILED,
                    $conversion->errorOrFail()->message,
                    $conversion->errorOrFail()->context,
                ));
            }

            $baseQuantity = (float) $conversion->valueOrFail();

            $batchId = isset($payload['batch_id']) ? (int) $payload['batch_id'] : null;
            if ($batchId !== null) {
                $batch = $this->batchRepository->findById($batchId);
                if (
                    $batch === null
                    || (int) $batch->get('tenant_id') !== $tenantId
                    || (int) $batch->get('item_id') !== $itemId
                ) {
                    return Result::failure(new Error(
                        InventoryErrorCode::INVALID_BATCH,
                        'batch_id must belong to the same tenant and item.',
                    ));
                }
            }

            $serialId = isset($payload['serial_id']) ? (int) $payload['serial_id'] : null;
            if ((bool) $item->get('is_serial_tracked', false) && $serialId === null) {
                return Result::failure(new Error(
                    InventoryErrorCode::INVALID_SERIAL,
                    'Serialized items require serial_id for stock movements.',
                ));
            }

            if ($serialId !== null) {
                $serial = $this->serialRepository->findById($serialId);
                if (
                    $serial === null
                    || (int) $serial->get('tenant_id') !== $tenantId
                    || (int) $serial->get('item_id') !== $itemId
                ) {
                    return Result::failure(new Error(
                        InventoryErrorCode::INVALID_SERIAL,
                        'serial_id must belong to the same tenant and item.',
                    ));
                }

                if ($baseQuantity !== 1.0) {
                    return Result::failure(new Error(
                        InventoryErrorCode::INVALID_SERIAL,
                        'Serialized stock movements must use a quantity of exactly 1 in the base unit.',
                    ));
                }
            }

            return $this->stockMovementRepository->transaction(function () use (
                $payload,
                $tenantId,
                $item,
                $uomId,
                $quantity,
                $baseUomId,
                $baseQuantity,
                $direction,
                $batchId,
                $serialId,
                $warehouseId,
            ): Result {
                $levelCriteria = [
                    'tenant_id' => $tenantId,
                    'organization_unit_id' => $payload['organization_unit_id'] ?? null,
                    'item_id' => (int) $item->id(),
                    'variant_id' => $payload['variant_id'] ?? null,
                    'warehouse_id' => $warehouseId,
                    'location_id' => $payload['location_id'] ?? null,
                    'batch_id' => $batchId,
                    'serial_id' => $serialId,
                    'base_uom_id' => $baseUomId,
                    'condition' => $payload['condition'] ?? 'good',
                ];

                $existingLevel = $this->findMatchingStockLevel($levelCriteria);
                $currentOnHand = (float) ($existingLevel?->get('quantity_on_hand', 0) ?? 0.0);
                $currentReserved = (float) ($existingLevel?->get('quantity_reserved', 0) ?? 0.0);
                $delta = $direction === 'IN' ? $baseQuantity : ($baseQuantity * -1);
                $nextOnHand = round($currentOnHand + $delta, 4);
                $allowNegativeStock = $this->allowNegativeStock();

                if (! $allowNegativeStock && $nextOnHand < 0) {
                    return Result::failure(new Error(
                        InventoryErrorCode::NEGATIVE_STOCK_NOT_ALLOWED,
                        'Stock movement would result in negative on-hand quantity.',
                    ));
                }

                if (! $allowNegativeStock && $nextOnHand < $currentReserved) {
                    return Result::failure(new Error(
                        InventoryErrorCode::NEGATIVE_STOCK_NOT_ALLOWED,
                        'Stock movement would reduce available stock below the reserved quantity.',
                    ));
                }

                $movementPayload = array_merge($payload, [
                    'row_version' => (int) ($payload['row_version'] ?? 1),
                    'transaction_uom_id' => $uomId,
                    'base_uom_id' => $baseUomId,
                    'quantity' => $quantity,
                    'base_quantity' => $baseQuantity,
                    'quantity_in' => $direction === 'IN' ? $quantity : 0,
                    'quantity_out' => $direction === 'OUT' ? $quantity : 0,
                    'base_quantity_in' => $direction === 'IN' ? $baseQuantity : 0,
                    'base_quantity_out' => $direction === 'OUT' ? $baseQuantity : 0,
                    'balance_quantity' => $nextOnHand,
                    'movement_type' => $payload['movement_type'] ?? 'OPENING_BALANCE',
                    'performed_at' => $payload['performed_at'] ?? now(),
                    'total_cost' => isset($payload['unit_cost'])
                        ? round($baseQuantity * (float) $payload['unit_cost'], 4)
                        : (float) ($payload['total_cost'] ?? 0),
                ]);

                $movement = $this->stockMovementRepository->create($movementPayload);

                $levelPayload = array_merge($levelCriteria, [
                    'row_version' => $existingLevel?->get('row_version', 1) ?? 1,
                    'quantity_on_hand' => $nextOnHand,
                    'quantity_reserved' => $currentReserved,
                    'unit_cost' => $payload['unit_cost'] ?? $existingLevel?->get('unit_cost'),
                    'last_movement_at' => $movement->get('performed_at'),
                ]);

                if ($existingLevel === null) {
                    $this->stockLevelRepository->create($levelPayload);
                } else {
                    $this->stockLevelRepository->update($existingLevel->id(), $levelPayload);
                }

                return Result::success($movement);
            });
        } catch (Throwable $exception) {
            return Result::failure(new Error(InventoryErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }

    /**
     * @param array<string, mixed> $criteria
     */
    private function findMatchingStockLevel(array $criteria): ?DataRecord
    {
        $matches = $this->stockLevelRepository->list($criteria);

        return $matches[0] ?? null;
    }

    private function allowNegativeStock(): bool
    {
        try {
            return (bool) config('inventory.allow_negative_stock', false);
        } catch (Throwable) {
            return false;
        }
    }
}
