<?php

declare(strict_types=1);

namespace Modules\Inventory\Services;

use InvalidArgumentException;
use Modules\Core\Services\DecimalMath;
use Modules\Inventory\DTOs\ValuationLayerData;
use Modules\Inventory\Enums\ValuationLayerStatus;
use Modules\Inventory\Enums\ValuationMethod;
use Modules\Inventory\Models\InventoryMovement;
use Modules\Inventory\Models\InventoryValuationLayer;

final class InventoryValuationService
{
    public function __construct(private readonly DecimalMath $math) {}

    public function createInboundLayer(ValuationLayerData $data): InventoryValuationLayer
    {
        $totalCost = $this->math->mul($data->originalQuantity, $data->unitCost);

        return InventoryValuationLayer::query()->create([
            'tenant_id' => $data->tenantId,
            'organization_unit_id' => $data->organizationUnitId,
            'item_id' => $data->itemId,
            'base_uom_id' => $data->baseUomId,
            'item_variant_id' => $data->itemVariantId,
            'warehouse_id' => $data->warehouseId,
            'warehouse_location_id' => $data->warehouseLocationId,
            'batch_id' => $data->batchId,
            'source_type' => $data->sourceType,
            'source_id' => $data->sourceId,
            'source_line_type' => $data->sourceLineType,
            'source_line_id' => $data->sourceLineId,
            'movement_id' => $data->movementId,
            'valuation_method' => $data->valuationMethod,
            'original_quantity' => $this->math->normalize($data->originalQuantity),
            'remaining_quantity' => $this->math->normalize($data->originalQuantity),
            'unit_cost' => $this->math->normalize($data->unitCost),
            'total_cost' => $totalCost,
            'remaining_value' => $totalCost,
            'status' => ValuationLayerStatus::Open,
        ]);
    }

    public function consumeOutbound(InventoryMovement $movement, string $quantity): string
    {
        $item = $movement->item;
        $method = $this->methodFromItem($item?->costing_method?->value ?? (string) $item?->costing_method);

        if ($method === ValuationMethod::WeightedAverage || $method === ValuationMethod::Standard || $method === ValuationMethod::Manual) {
            $unitCost = (string) $movement->unit_cost;

            return $this->math->mul($quantity, $unitCost);
        }

        $remaining = $this->math->normalize($quantity);
        $totalCost = '0.000000';
        $layers = InventoryValuationLayer::query()
            ->where('tenant_id', $movement->tenant_id)
            ->where('item_id', $movement->item_id)
            ->where('warehouse_id', $movement->warehouse_id)
            ->where('status', ValuationLayerStatus::Open->value)
            ->orderBy('id')
            ->get();

        foreach ($layers as $layer) {
            if ($this->math->isZero($remaining)) {
                break;
            }

            $take = $this->math->compare((string) $layer->remaining_quantity, $remaining) >= 0
                ? $remaining
                : (string) $layer->remaining_quantity;
            $cost = $this->math->mul($take, (string) $layer->unit_cost);

            $layer->remaining_quantity = $this->math->sub((string) $layer->remaining_quantity, $take);
            $layer->remaining_value = $this->math->sub((string) $layer->remaining_value, $cost);
            if ($this->math->isZero((string) $layer->remaining_quantity)) {
                $layer->status = ValuationLayerStatus::Closed;
                $layer->remaining_value = '0.000000';
            }
            $layer->save();

            $remaining = $this->math->sub($remaining, $take);
            $totalCost = $this->math->add($totalCost, $cost);
        }

        if (! $this->math->isZero($remaining)) {
            throw new InvalidArgumentException('Insufficient valuation layers for FIFO issue.');
        }

        return $totalCost;
    }

    public function methodFromItem(?string $costingMethod): ValuationMethod
    {
        return match ($costingMethod) {
            'weighted_average' => ValuationMethod::WeightedAverage,
            'standard' => ValuationMethod::Standard,
            'manual' => ValuationMethod::Manual,
            default => ValuationMethod::FIFO,
        };
    }
}
