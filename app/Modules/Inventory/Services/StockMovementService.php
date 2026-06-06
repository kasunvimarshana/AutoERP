<?php

declare(strict_types=1);

namespace Modules\Inventory\Services;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Core\Services\DecimalMath;
use Modules\Inventory\DTOs\StockBalanceData;
use Modules\Inventory\DTOs\StockMovementData;
use Modules\Inventory\DTOs\StockPostingResult;
use Modules\Inventory\DTOs\ValuationLayerData;
use Modules\Inventory\Enums\InventoryDirection;
use Modules\Inventory\Enums\InventoryMovementType;
use Modules\Inventory\Enums\InventoryStatus;
use Modules\Inventory\Models\InventoryMovement;
use Modules\Inventory\Validators\InventoryValidationService;

final class StockMovementService
{
    public function __construct(
        private readonly DecimalMath $math,
        private readonly InventoryValidationService $validator,
        private readonly InventoryNumberService $numbers,
        private readonly StockBalanceService $balances,
        private readonly InventoryValuationService $valuation,
    ) {}

    public function create(StockMovementData $data): InventoryMovement
    {
        $quantity = $this->math->normalize($data->quantity);
        $this->validator->assertPositiveQuantity($quantity);
        $this->validator->assertNonNegative($data->unitCost, 'Inventory unit cost cannot be negative.');

        $item = $this->validator->item($data->tenantId, $data->organizationUnitId, $data->itemId);
        $this->validator->assertStockable($item);
        $this->validator->variant($item, $data->itemVariantId);
        $warehouse = $this->validator->warehouse($data->tenantId, $data->organizationUnitId, $data->warehouseId);
        $this->validator->location($warehouse, $data->warehouseLocationId);
        $this->validator->batch($item, $data->batchId);
        $this->validator->serial($item, $data->serialNumberId, $quantity);

        return InventoryMovement::query()->create([
            'tenant_id' => $data->tenantId,
            'organization_unit_id' => $data->organizationUnitId,
            'movement_number' => $data->movementNumber ?? $this->numbers->next($data->tenantId, 'MOV', 'inventory_movements', 'movement_number'),
            'movement_date' => $data->movementDate,
            'movement_type' => $data->movementType,
            'direction' => $data->direction,
            'item_id' => $data->itemId,
            'item_variant_id' => $data->itemVariantId,
            'warehouse_id' => $data->warehouseId,
            'warehouse_location_id' => $data->warehouseLocationId,
            'batch_id' => $data->batchId,
            'serial_number_id' => $data->serialNumberId,
            'quantity' => $quantity,
            'unit_cost' => $this->math->normalize($data->unitCost),
            'total_cost' => $this->math->mul($quantity, $data->unitCost),
            'source_type' => $data->sourceType,
            'source_id' => $data->sourceId,
            'source_line_type' => $data->sourceLineType,
            'source_line_id' => $data->sourceLineId,
            'status' => InventoryStatus::Draft,
            'description' => $data->description,
            'created_by' => $data->createdBy,
        ]);
    }

    public function record(StockMovementData $data, ?int $postedBy = null): InventoryMovement
    {
        return $this->post($this->create($data), $postedBy);
    }

    public function post(InventoryMovement $movement, ?int $postedBy = null): InventoryMovement
    {
        if ($movement->status !== InventoryStatus::Draft) {
            throw new InvalidArgumentException('Only draft inventory movements can be posted.');
        }

        return DB::transaction(function () use ($movement, $postedBy): InventoryMovement {
            $balance = $this->balances->getOrCreate($this->balanceData($movement));
            $quantity = (string) $movement->quantity;
            $unitCost = (string) $movement->unit_cost;
            $totalCost = (string) $movement->total_cost;

            if ($movement->direction === InventoryDirection::In) {
                $this->balances->increase($balance, $quantity, $unitCost);
                $this->valuation->createInboundLayer(new ValuationLayerData(
                    tenantId: (int) $movement->tenant_id,
                    itemId: (int) $movement->item_id,
                    warehouseId: (int) $movement->warehouse_id,
                    valuationMethod: $this->valuation->methodFromItem($movement->item?->costing_method?->value ?? null),
                    originalQuantity: $quantity,
                    unitCost: $unitCost,
                    organizationUnitId: $movement->organization_unit_id,
                    itemVariantId: $movement->item_variant_id,
                    warehouseLocationId: $movement->warehouse_location_id,
                    batchId: $movement->batch_id,
                    movementId: (int) $movement->getKey(),
                    sourceType: $movement->source_type,
                    sourceId: $movement->source_id,
                    sourceLineType: $movement->source_line_type,
                    sourceLineId: $movement->source_line_id,
                ));
            } elseif ($movement->direction === InventoryDirection::Out) {
                if ($this->math->compare((string) $balance->quantity_available, $quantity) < 0) {
                    throw new InvalidArgumentException('Inventory issue quantity cannot exceed available stock.');
                }

                if ($this->math->isZero($unitCost)) {
                    $unitCost = (string) $balance->average_cost;
                }

                $movement->unit_cost = $unitCost;
                $totalCost = $this->valuation->consumeOutbound($movement, $quantity);
                $movement->total_cost = $totalCost;
                $this->balances->decrease($balance, $quantity, $this->math->div($totalCost, $quantity));
            }

            $balance->refresh();
            $movement->balance_quantity_after = $balance->quantity_on_hand;
            $movement->balance_value_after = $balance->total_value;
            $movement->status = InventoryStatus::Posted;
            $movement->posted_by = $postedBy;
            $movement->posted_at = now();
            $movement->save();

            return $movement->refresh();
        });
    }

    public function reverse(InventoryMovement $movement, ?int $reversedBy = null): InventoryMovement
    {
        if ($movement->status !== InventoryStatus::Posted) {
            throw new InvalidArgumentException('Only posted inventory movements can be reversed.');
        }

        $direction = $movement->direction === InventoryDirection::In ? InventoryDirection::Out : InventoryDirection::In;
        $type = $direction === InventoryDirection::In ? InventoryMovementType::AdjustmentIn : InventoryMovementType::AdjustmentOut;

        $reversal = $this->record(new StockMovementData(
            tenantId: (int) $movement->tenant_id,
            movementDate: now()->toDateString(),
            movementType: $type,
            direction: $direction,
            itemId: (int) $movement->item_id,
            warehouseId: (int) $movement->warehouse_id,
            quantity: (string) $movement->quantity,
            organizationUnitId: $movement->organization_unit_id,
            itemVariantId: $movement->item_variant_id,
            warehouseLocationId: $movement->warehouse_location_id,
            batchId: $movement->batch_id,
            serialNumberId: $movement->serial_number_id,
            unitCost: (string) $movement->unit_cost,
            sourceType: 'inventory_movement',
            sourceId: (int) $movement->getKey(),
            description: 'Reversal of '.$movement->movement_number,
        ), $reversedBy);

        $movement->status = InventoryStatus::Reversed;
        $movement->reversed_by = $reversedBy;
        $movement->reversed_at = now();
        $movement->save();
        $reversal->reversal_of_id = $movement->getKey();
        $reversal->save();

        return $reversal->refresh();
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

    private function balanceData(InventoryMovement $movement): StockBalanceData
    {
        return new StockBalanceData(
            tenantId: (int) $movement->tenant_id,
            itemId: (int) $movement->item_id,
            warehouseId: (int) $movement->warehouse_id,
            organizationUnitId: $movement->organization_unit_id,
            itemVariantId: $movement->item_variant_id,
            warehouseLocationId: $movement->warehouse_location_id,
            batchId: $movement->batch_id,
        );
    }
}
