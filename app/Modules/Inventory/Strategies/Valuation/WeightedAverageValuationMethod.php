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

final class WeightedAverageValuationMethod implements ValuationMethodInterface
{
    public function __construct(private readonly DecimalMath $math) {}

    public function receive(InventoryMovement $movement): ValuationResultData
    {
        $quantity = $this->math->normalize((string) $movement->quantity);
        $receiptCost = $this->math->normalize((string) $movement->unit_cost);
        $receiptValue = $this->math->mul($quantity, $receiptCost);
        $layer = $this->poolQuery($movement)->lockForUpdate()->first();

        if (! $layer instanceof InventoryValuationLayer) {
            $layer = InventoryValuationLayer::query()->create([
                ...$this->scope($movement),
                'base_uom_id' => $movement->base_uom_id,
                'source_type' => $movement->source_type,
                'source_id' => $movement->source_id,
                'source_line_type' => $movement->source_line_type,
                'source_line_id' => $movement->source_line_id,
                'movement_id' => $movement->getKey(),
                'valuation_method' => ValuationMethod::WeightedAverage,
                'original_quantity' => $quantity,
                'remaining_quantity' => $quantity,
                'unit_cost' => $receiptCost,
                'total_cost' => $receiptValue,
                'remaining_value' => $receiptValue,
                'status' => ValuationLayerStatus::Open,
            ]);
        } else {
            $newQuantity = $this->math->add((string) $layer->remaining_quantity, $quantity);
            $newValue = $this->math->add((string) $layer->remaining_value, $receiptValue);
            $layer->original_quantity = $this->math->add((string) $layer->original_quantity, $quantity);
            $layer->remaining_quantity = $newQuantity;
            $layer->total_cost = $this->math->add((string) $layer->total_cost, $receiptValue);
            $layer->remaining_value = $newValue;
            $layer->unit_cost = $this->math->div($newValue, $newQuantity);
            $layer->status = ValuationLayerStatus::Open;
            $layer->save();
        }

        return new ValuationResultData($quantity, $receiptCost, $receiptValue);
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
        $layer = $this->poolQuery($movement)->first();
        if (! $layer instanceof InventoryValuationLayer) {
            return new ValuationResultData('0.000000', '0.000000', '0.000000');
        }

        return new ValuationResultData(
            (string) $layer->remaining_quantity,
            (string) $layer->unit_cost,
            (string) $layer->remaining_value,
        );
    }

    public function reverse(InventoryMovement $movement, InventoryMovement $reversal): ValuationResultData
    {
        if ($movement->direction === InventoryDirection::Out) {
            $consumption = InventoryValuationConsumption::query()
                ->where('issue_movement_id', $movement->getKey())
                ->whereNull('reversed_by_movement_id')
                ->lockForUpdate()
                ->firstOrFail();
            $layer = InventoryValuationLayer::query()->lockForUpdate()->findOrFail($consumption->valuation_layer_id);
            $layer->remaining_quantity = $this->math->add((string) $layer->remaining_quantity, (string) $consumption->quantity_consumed);
            $layer->remaining_value = $this->math->add((string) $layer->remaining_value, (string) $consumption->total_cost);
            $layer->unit_cost = $this->math->div((string) $layer->remaining_value, (string) $layer->remaining_quantity);
            $layer->status = ValuationLayerStatus::Open;
            $layer->save();
            $consumption->reversed_by_movement_id = $reversal->getKey();
            $consumption->reversed_at = now();
            $consumption->save();

            return new ValuationResultData(
                (string) $consumption->quantity_consumed,
                (string) $consumption->unit_cost,
                (string) $consumption->total_cost,
            );
        }

        $layer = $this->poolQuery($movement)->lockForUpdate()->firstOrFail();
        $quantity = $this->math->normalize((string) $reversal->quantity);
        $totalCost = $this->math->normalize((string) $movement->total_cost);
        if ($this->math->compare((string) $layer->remaining_quantity, $quantity) < 0
            || $this->math->compare((string) $layer->remaining_value, $totalCost) < 0) {
            throw new InvalidArgumentException('Weighted-average receipt cannot be reversed after its stock value was consumed.');
        }

        $layer->remaining_quantity = $this->math->sub((string) $layer->remaining_quantity, $quantity);
        $layer->remaining_value = $this->math->sub((string) $layer->remaining_value, $totalCost);
        if ($this->math->isZero((string) $layer->remaining_quantity)) {
            $layer->unit_cost = '0.000000';
            $layer->remaining_value = '0.000000';
            $layer->status = ValuationLayerStatus::Closed;
        } else {
            $layer->unit_cost = $this->math->div((string) $layer->remaining_value, (string) $layer->remaining_quantity);
        }
        $layer->save();

        InventoryValuationConsumption::query()->create([
            'tenant_id' => $reversal->tenant_id,
            'organization_unit_id' => $reversal->organization_unit_id,
            'issue_movement_id' => $reversal->getKey(),
            'valuation_layer_id' => $layer->getKey(),
            'quantity_consumed' => $quantity,
            'unit_cost' => $reversal->unit_cost,
            'total_cost' => $totalCost,
        ]);

        return new ValuationResultData($quantity, (string) $reversal->unit_cost, $totalCost);
    }

    private function consume(InventoryMovement $movement, string $quantity, bool $persist): ValuationResultData
    {
        $quantity = $this->math->normalize($quantity);
        $layer = $persist
            ? $this->poolQuery($movement)->lockForUpdate()->first()
            : $this->poolQuery($movement)->first();
        if (! $layer instanceof InventoryValuationLayer
            || $this->math->compare((string) $layer->remaining_quantity, $quantity) < 0) {
            throw new InvalidArgumentException('Insufficient weighted-average valuation quantity for inventory issue.');
        }

        $unitCost = (string) $layer->unit_cost;
        $totalCost = $this->math->mul($quantity, $unitCost);
        if ($persist) {
            $layer->remaining_quantity = $this->math->sub((string) $layer->remaining_quantity, $quantity);
            $layer->remaining_value = $this->math->sub((string) $layer->remaining_value, $totalCost);
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
                'quantity_consumed' => $quantity,
                'unit_cost' => $unitCost,
                'total_cost' => $totalCost,
            ]);
        }

        return new ValuationResultData($quantity, $unitCost, $totalCost);
    }

    private function poolQuery(InventoryMovement $movement): Builder
    {
        $query = InventoryValuationLayer::query()
            ->where('tenant_id', $movement->tenant_id)
            ->where('item_id', $movement->item_id)
            ->where('warehouse_id', $movement->warehouse_id)
            ->where('valuation_method', ValuationMethod::WeightedAverage->value)
            ->where('status', ValuationLayerStatus::Open->value);
        foreach (['organization_unit_id', 'item_variant_id', 'warehouse_location_id', 'batch_id'] as $column) {
            $query->where($column, $movement->{$column});
        }

        return $query->orderBy('id');
    }

    /**
     * @return array<string, int|null>
     */
    private function scope(InventoryMovement $movement): array
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
}
