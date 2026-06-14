<?php

declare(strict_types=1);

namespace Modules\Inventory\Services;

use Modules\Core\Services\DecimalMath;
use Modules\Inventory\DTOs\StockMovementData;
use Modules\Inventory\DTOs\StockPostingResult;
use Modules\Inventory\Enums\InventoryDirection;
use Modules\Inventory\Enums\InventoryStatus;
use Modules\Inventory\Enums\InventoryStockState;
use Modules\Inventory\Models\InventoryMovement;
use Modules\Inventory\Validators\InventoryValidationService;

final class InventoryMovementRecorder
{
    public function __construct(
        private readonly DecimalMath $math,
        private readonly InventoryValidationService $validator,
        private readonly InventoryNumberService $numbers,
        private readonly InventoryUomService $uoms,
    ) {}

    public function create(StockMovementData $data): InventoryMovement
    {
        $quantity = $this->math->normalize($data->quantity);
        $unitCost = $this->math->normalize($data->unitCost);
        $this->validator->assertPositiveQuantity($quantity);
        $this->validator->assertNonNegative($unitCost, 'Inventory unit cost cannot be negative.');

        $item = $this->validator->item($data->tenantId, $data->organizationUnitId, $data->itemId);
        $this->validator->assertStockable($item);
        $basis = $this->uoms->basis(
            $data->tenantId,
            $data->organizationUnitId,
            $item,
            $data->uomId,
            $quantity,
            $unitCost,
        );
        $quantity = $basis->baseQuantity;
        $unitCost = $basis->baseUnitCost;
        $item = $item->refresh();
        $this->validator->variant($item, $data->itemVariantId);
        $warehouse = $this->validator->warehouse($data->tenantId, $data->organizationUnitId, $data->warehouseId);
        $this->validator->location($warehouse, $data->warehouseLocationId);
        $this->validator->batch($item, $data->batchId, $data->itemVariantId);
        $this->validator->serial(
            $item,
            $data->serialNumberId,
            $quantity,
            $data->itemVariantId,
            $data->batchId,
        );

        return InventoryMovement::query()->create([
            'tenant_id' => $data->tenantId,
            'organization_unit_id' => $data->organizationUnitId,
            'movement_number' => $data->movementNumber ?? $this->numbers->next($data->tenantId, 'MOV'),
            'movement_date' => $data->movementDate,
            'movement_type' => $data->movementType,
            'direction' => $data->direction,
            'item_id' => $data->itemId,
            'base_uom_id' => $basis->baseUomId,
            'entered_uom_id' => $basis->enteredUomId,
            'item_variant_id' => $data->itemVariantId,
            'warehouse_id' => $data->warehouseId,
            'warehouse_location_id' => $data->warehouseLocationId,
            'batch_id' => $data->batchId,
            'serial_number_id' => $data->serialNumberId,
            'entered_quantity' => $basis->enteredQuantity,
            'entered_unit_cost' => $basis->enteredUnitCost,
            'conversion_factor' => $basis->conversionFactor,
            'quantity' => $quantity,
            'unit_cost' => $unitCost,
            'total_cost' => $this->math->mul($quantity, $unitCost),
            'source_type' => $data->sourceType,
            'source_id' => $data->sourceId,
            'source_line_type' => $data->sourceLineType,
            'source_line_id' => $data->sourceLineId,
            'from_state' => $data->fromState ?? $this->defaultFromState($data->direction),
            'to_state' => $data->toState ?? $this->defaultToState($data->direction),
            'status' => InventoryStatus::Draft,
            'description' => $data->description,
            'created_by' => $data->createdBy,
        ]);
    }

    public function result(InventoryMovement $movement): StockPostingResult
    {
        return new StockPostingResult(
            movementId: (int) $movement->getKey(),
            movementNumber: (string) $movement->movement_number,
            status: $movement->status instanceof InventoryStatus ? $movement->status->value : (string) $movement->status,
            quantity: (string) $movement->quantity,
            unitCost: (string) $movement->unit_cost,
            totalCost: (string) $movement->total_cost,
            balanceQuantityAfter: (string) $movement->balance_quantity_after,
            balanceValueAfter: (string) $movement->balance_value_after,
        );
    }

    private function defaultFromState(InventoryDirection $direction): ?InventoryStockState
    {
        return $direction === InventoryDirection::Out ? InventoryStockState::Available : null;
    }

    private function defaultToState(InventoryDirection $direction): InventoryStockState
    {
        return $direction === InventoryDirection::Out
            ? InventoryStockState::Issued
            : InventoryStockState::Available;
    }
}
