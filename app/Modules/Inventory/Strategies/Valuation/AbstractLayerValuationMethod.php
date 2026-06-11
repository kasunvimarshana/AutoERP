<?php

declare(strict_types=1);

namespace Modules\Inventory\Strategies\Valuation;

use Illuminate\Database\Eloquent\Builder;
use InvalidArgumentException;
use Modules\Core\Services\DecimalMath;
use Modules\Inventory\Contracts\ValuationMethodInterface;
use Modules\Inventory\DTOs\ValuationResultData;
use Modules\Inventory\Enums\InventoryDirection;
use Modules\Inventory\Enums\ValuationLayerStatus;
use Modules\Inventory\Enums\ValuationMethod;
use Modules\Inventory\Models\InventoryMovement;
use Modules\Inventory\Models\InventoryValuationConsumption;
use Modules\Inventory\Models\InventoryValuationLayer;

abstract class AbstractLayerValuationMethod implements ValuationMethodInterface
{
    public function __construct(protected readonly DecimalMath $math) {}

    public function receive(InventoryMovement $movement): ValuationResultData
    {
        $quantity = $this->math->normalize((string) $movement->quantity);
        $unitCost = $this->receiptUnitCost($movement);
        $totalCost = $this->math->mul($quantity, $unitCost);

        InventoryValuationLayer::query()->create([
            ...$this->layerScope($movement),
            'base_uom_id' => $movement->base_uom_id,
            'source_type' => $movement->source_type,
            'source_id' => $movement->source_id,
            'source_line_type' => $movement->source_line_type,
            'source_line_id' => $movement->source_line_id,
            'movement_id' => $movement->getKey(),
            'valuation_method' => $this->method(),
            'original_quantity' => $quantity,
            'remaining_quantity' => $quantity,
            'unit_cost' => $unitCost,
            'total_cost' => $totalCost,
            'remaining_value' => $totalCost,
            'status' => ValuationLayerStatus::Open,
        ]);

        return new ValuationResultData($quantity, $unitCost, $totalCost);
    }

    public function issue(InventoryMovement $movement, string $quantity): ValuationResultData
    {
        return $this->consume($movement, $quantity, true);
    }

    public function preview(InventoryMovement $movement, string $quantity): ValuationResultData
    {
        return $this->consume($movement, $quantity, false);
    }

    public function recalculate(InventoryMovement $movement): ValuationResultData
    {
        return $this->preview($movement, (string) $movement->quantity);
    }

    public function reverse(InventoryMovement $movement, InventoryMovement $reversal): ValuationResultData
    {
        return $movement->direction === InventoryDirection::Out
            ? $this->reverseIssue($movement, $reversal)
            : $this->reverseReceipt($movement, $reversal);
    }

    abstract protected function method(): ValuationMethod;

    protected function receiptUnitCost(InventoryMovement $movement): string
    {
        return $this->math->normalize((string) $movement->unit_cost);
    }

    protected function layerQuery(InventoryMovement $movement): Builder
    {
        $query = InventoryValuationLayer::query()
            ->where('tenant_id', $movement->tenant_id)
            ->where('item_id', $movement->item_id)
            ->where('warehouse_id', $movement->warehouse_id)
            ->where('status', ValuationLayerStatus::Open->value);

        foreach ([
            'organization_unit_id',
            'item_variant_id',
            'warehouse_location_id',
            'batch_id',
        ] as $column) {
            $query->where($column, $movement->{$column});
        }

        return $query->orderBy('created_at')->orderBy('id');
    }

    /**
     * @return array<string, int|null>
     */
    protected function layerScope(InventoryMovement $movement): array
    {
        return [
            'tenant_id' => (int) $movement->tenant_id,
            'organization_unit_id' => $movement->organization_unit_id,
            'item_id' => (int) $movement->item_id,
            'item_variant_id' => $movement->item_variant_id,
            'warehouse_id' => (int) $movement->warehouse_id,
            'warehouse_location_id' => $movement->warehouse_location_id,
            'batch_id' => $movement->batch_id,
        ];
    }

    private function consume(InventoryMovement $movement, string $quantity, bool $persist): ValuationResultData
    {
        $remaining = $this->math->normalize($quantity);
        $totalCost = '0.000000';
        $weightedQuantity = '0.000000';
        $layers = $persist
            ? $this->layerQuery($movement)->lockForUpdate()->get()
            : $this->layerQuery($movement)->get();

        foreach ($layers as $layer) {
            if ($this->math->isZero($remaining)) {
                break;
            }

            $take = $this->math->compare((string) $layer->remaining_quantity, $remaining) >= 0
                ? $remaining
                : (string) $layer->remaining_quantity;
            $cost = $this->math->mul($take, (string) $layer->unit_cost);
            $totalCost = $this->math->add($totalCost, $cost);
            $weightedQuantity = $this->math->add($weightedQuantity, $take);

            if ($persist) {
                $layer->remaining_quantity = $this->math->sub((string) $layer->remaining_quantity, $take);
                $layer->remaining_value = $this->math->sub((string) $layer->remaining_value, $cost);
                if ($this->math->isZero((string) $layer->remaining_quantity)) {
                    $layer->remaining_value = '0.000000';
                    $layer->status = ValuationLayerStatus::Closed;
                }
                $layer->save();

                InventoryValuationConsumption::query()->create([
                    'tenant_id' => $movement->tenant_id,
                    'organization_unit_id' => $movement->organization_unit_id,
                    'issue_movement_id' => $movement->getKey(),
                    'valuation_layer_id' => $layer->getKey(),
                    'quantity_consumed' => $take,
                    'unit_cost' => $layer->unit_cost,
                    'total_cost' => $cost,
                ]);
            }

            $remaining = $this->math->sub($remaining, $take);
        }

        if (! $this->math->isZero($remaining)) {
            throw new InvalidArgumentException('Insufficient valuation layers for inventory issue.');
        }

        return new ValuationResultData(
            $weightedQuantity,
            $this->math->div($totalCost, $weightedQuantity),
            $totalCost,
        );
    }

    private function reverseIssue(InventoryMovement $movement, InventoryMovement $reversal): ValuationResultData
    {
        $consumptions = InventoryValuationConsumption::query()
            ->where('issue_movement_id', $movement->getKey())
            ->whereNull('reversed_by_movement_id')
            ->orderBy('id')
            ->lockForUpdate()
            ->get();
        if ($consumptions->isEmpty()) {
            throw new InvalidArgumentException('Inventory issue has no reversible valuation consumption audit.');
        }

        $quantity = '0.000000';
        $totalCost = '0.000000';
        foreach ($consumptions as $consumption) {
            $layer = InventoryValuationLayer::query()->lockForUpdate()->findOrFail($consumption->valuation_layer_id);
            $layer->remaining_quantity = $this->math->add((string) $layer->remaining_quantity, (string) $consumption->quantity_consumed);
            $layer->remaining_value = $this->math->add((string) $layer->remaining_value, (string) $consumption->total_cost);
            $layer->status = ValuationLayerStatus::Open;
            $layer->save();

            $consumption->reversed_by_movement_id = $reversal->getKey();
            $consumption->reversed_at = now();
            $consumption->save();

            $quantity = $this->math->add($quantity, (string) $consumption->quantity_consumed);
            $totalCost = $this->math->add($totalCost, (string) $consumption->total_cost);
        }

        return new ValuationResultData($quantity, $this->math->div($totalCost, $quantity), $totalCost);
    }

    private function reverseReceipt(InventoryMovement $movement, InventoryMovement $reversal): ValuationResultData
    {
        $remaining = $this->math->normalize((string) $reversal->quantity);
        $totalCost = '0.000000';
        $layers = InventoryValuationLayer::query()
            ->where('movement_id', $movement->getKey())
            ->orderBy('id')
            ->lockForUpdate()
            ->get();

        foreach ($layers as $layer) {
            if ($this->math->isZero($remaining)) {
                break;
            }
            if ($this->math->isZero((string) $layer->remaining_quantity)) {
                continue;
            }

            $take = $this->math->compare((string) $layer->remaining_quantity, $remaining) >= 0
                ? $remaining
                : (string) $layer->remaining_quantity;
            $cost = $this->math->mul($take, (string) $layer->unit_cost);
            $layer->remaining_quantity = $this->math->sub((string) $layer->remaining_quantity, $take);
            $layer->remaining_value = $this->math->sub((string) $layer->remaining_value, $cost);
            if ($this->math->isZero((string) $layer->remaining_quantity)) {
                $layer->remaining_value = '0.000000';
                $layer->status = ValuationLayerStatus::Closed;
            }
            $layer->save();

            InventoryValuationConsumption::query()->create([
                'tenant_id' => $reversal->tenant_id,
                'organization_unit_id' => $reversal->organization_unit_id,
                'issue_movement_id' => $reversal->getKey(),
                'valuation_layer_id' => $layer->getKey(),
                'quantity_consumed' => $take,
                'unit_cost' => $layer->unit_cost,
                'total_cost' => $cost,
            ]);

            $remaining = $this->math->sub($remaining, $take);
            $totalCost = $this->math->add($totalCost, $cost);
        }

        if (! $this->math->isZero($remaining)) {
            throw new InvalidArgumentException('Receipt cannot be reversed because its valuation layer was already consumed.');
        }

        $quantity = $this->math->normalize((string) $reversal->quantity);

        return new ValuationResultData($quantity, $this->math->div($totalCost, $quantity), $totalCost);
    }
}
