<?php

declare(strict_types=1);

namespace Modules\Inventory\Services\Item;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Modules\Core\Services\DecimalMath;
use Modules\Inventory\Models\InventoryAllocation;
use Modules\Inventory\Models\InventoryAllocationLine;
use Modules\Inventory\Models\InventoryReservation;
use Modules\Inventory\Models\InventoryStockBalance;
use Modules\Inventory\Models\InventoryValuationConsumption;
use Modules\Inventory\Models\InventoryValuationLayer;
use Modules\Item\Contracts\InventoryBaseUomConversionInterface;
use Modules\Item\Data\InventoryBaseUomConversionData;
use Modules\Item\Models\Item;

final class InventoryBaseUomConversionAdapter implements InventoryBaseUomConversionInterface
{
    private const BALANCE_QUANTITY_COLUMNS = [
        'quantity_on_hand',
        'quantity_reserved',
        'quantity_allocated',
        'quantity_available',
        'quantity_returned',
        'quantity_in_transit',
        'quantity_damaged',
        'quantity_quarantine',
        'quantity_expired',
        'quantity_scrapped',
    ];

    private const RESERVATION_QUANTITY_COLUMNS = [
        'quantity_reserved',
        'quantity_allocated',
        'quantity_released',
        'quantity_remaining',
    ];

    private const ALLOCATION_QUANTITY_COLUMNS = [
        'quantity_allocated',
        'quantity_issued',
        'quantity_reversed',
        'quantity_released',
        'quantity_remaining',
    ];

    public function __construct(
        private readonly DecimalMath $math,
    ) {}

    public function convert(Item $item, int $newBaseUomId, string $conversionFactor): void
    {
        $balances = $this->scope(InventoryStockBalance::query(), $item)->lockForUpdate()->get();
        foreach ($balances as $balance) {
            $balance->base_uom_id = $newBaseUomId;
            foreach (self::BALANCE_QUANTITY_COLUMNS as $column) {
                $balance->{$column} = $this->math->mul((string) $balance->{$column}, $conversionFactor);
            }
            $balance->average_cost = $this->math->div((string) $balance->average_cost, $conversionFactor);
            $balance->save();
        }

        $reservations = $this->scope(InventoryReservation::query(), $item)
            ->whereIn('status', ['active', 'partially_allocated'])
            ->lockForUpdate()
            ->get();
        foreach ($reservations as $reservation) {
            $reservation->base_uom_id = $newBaseUomId;
            $reservation->conversion_factor = $this->math->mul(
                (string) $reservation->conversion_factor,
                $conversionFactor,
            );
            foreach (self::RESERVATION_QUANTITY_COLUMNS as $column) {
                $reservation->{$column} = $this->math->mul((string) $reservation->{$column}, $conversionFactor);
            }
            $reservation->save();
        }

        $allocations = $this->scope(InventoryAllocation::query(), $item)
            ->where('status', 'active')
            ->lockForUpdate()
            ->get();
        foreach ($allocations as $allocation) {
            $allocation->base_uom_id = $newBaseUomId;
            $allocation->conversion_factor = $this->math->mul(
                (string) $allocation->conversion_factor,
                $conversionFactor,
            );
            foreach (self::ALLOCATION_QUANTITY_COLUMNS as $column) {
                $allocation->{$column} = $this->math->mul((string) $allocation->{$column}, $conversionFactor);
            }
            $allocation->save();
        }

        $allocationLines = InventoryAllocationLine::query()
            ->whereIn('allocation_id', $allocations->modelKeys())
            ->lockForUpdate()
            ->get();
        foreach ($allocationLines as $allocationLine) {
            foreach (self::ALLOCATION_QUANTITY_COLUMNS as $column) {
                $allocationLine->{$column} = $this->math->mul((string) $allocationLine->{$column}, $conversionFactor);
            }
            $allocationLine->save();
        }

        $layers = $this->scope(InventoryValuationLayer::query(), $item)
            ->where('status', 'open')
            ->lockForUpdate()
            ->get();
        foreach ($layers as $layer) {
            $layer->base_uom_id = $newBaseUomId;
            $layer->original_quantity = $this->math->mul((string) $layer->original_quantity, $conversionFactor);
            $layer->remaining_quantity = $this->math->mul((string) $layer->remaining_quantity, $conversionFactor);
            $layer->unit_cost = $this->math->div((string) $layer->unit_cost, $conversionFactor);
            $layer->save();
        }

        $consumptions = InventoryValuationConsumption::query()
            ->whereNull('reversed_by_movement_id')
            ->whereHas('valuationLayer', fn (Builder $query): Builder => $this->scope($query, $item))
            ->lockForUpdate()
            ->get();
        foreach ($consumptions as $consumption) {
            $consumption->quantity_consumed = $this->math->mul(
                (string) $consumption->quantity_consumed,
                $conversionFactor,
            );
            $consumption->unit_cost = $this->math->div((string) $consumption->unit_cost, $conversionFactor);
            $consumption->save();
        }
    }

    public function preview(Item $item, string $conversionFactor): InventoryBaseUomConversionData
    {
        $rows = [];
        $balances = $this->scope(InventoryStockBalance::query(), $item)
            ->with(['warehouse', 'warehouseLocation'])
            ->orderBy('id')
            ->get();
        foreach ($balances as $balance) {
            foreach (['quantity_on_hand', 'quantity_reserved', 'quantity_allocated', 'quantity_available'] as $metric) {
                $rows[] = [
                    'area' => 'stock_balance',
                    'reference' => trim(implode(' / ', array_filter([
                        $balance->warehouse?->name,
                        $balance->warehouseLocation?->name,
                    ]))) ?: 'Stock balance #'.$balance->getKey(),
                    'metric' => $metric,
                    'before' => $this->math->normalize((string) $balance->{$metric}),
                    'after' => $this->math->mul((string) $balance->{$metric}, $conversionFactor),
                ];
            }
            $rows[] = [
                'area' => 'stock_balance',
                'reference' => $balance->warehouse?->name ?: 'Stock balance #'.$balance->getKey(),
                'metric' => 'average_cost',
                'before' => $this->math->normalize((string) $balance->average_cost),
                'after' => $this->math->div((string) $balance->average_cost, $conversionFactor),
            ];
        }

        $rows = [
            ...$rows,
            ...$this->flowSummary($item, 'reservation', 'inventory_reservations', 'quantity_remaining', $conversionFactor),
            ...$this->flowSummary($item, 'allocation', 'inventory_allocations', 'quantity_remaining', $conversionFactor),
        ];

        $layers = $this->scope(InventoryValuationLayer::query(), $item)
            ->where('status', 'open')
            ->orderBy('id')
            ->get();
        foreach ($layers as $layer) {
            foreach (['original_quantity', 'remaining_quantity'] as $metric) {
                $rows[] = [
                    'area' => 'valuation_layer',
                    'reference' => 'Layer #'.$layer->getKey(),
                    'metric' => $metric,
                    'before' => $this->math->normalize((string) $layer->{$metric}),
                    'after' => $this->math->mul((string) $layer->{$metric}, $conversionFactor),
                ];
            }
            $rows[] = [
                'area' => 'valuation_layer',
                'reference' => 'Layer #'.$layer->getKey(),
                'metric' => 'unit_cost',
                'before' => $this->math->normalize((string) $layer->unit_cost),
                'after' => $this->math->div((string) $layer->unit_cost, $conversionFactor),
            ];
        }

        return new InventoryBaseUomConversionData($rows);
    }

    /**
     * @return list<array<string, string>>
     */
    private function flowSummary(
        Item $item,
        string $area,
        string $table,
        string $column,
        string $conversionFactor,
    ): array {
        $query = DB::table($table)
            ->where('tenant_id', $item->tenant_id)
            ->where('item_id', $item->getKey());
        if ($item->organization_unit_id !== null) {
            $query->where('organization_unit_id', $item->organization_unit_id);
        }

        $values = $query->pluck($column)->map(static fn ($value): string => (string) $value)->all();
        $before = $this->math->sum($values);

        return [[
            'area' => $area,
            'reference' => ucfirst($area).' total',
            'metric' => $column,
            'before' => $before,
            'after' => $this->math->mul($before, $conversionFactor),
        ]];
    }

    /**
     * @template TModel of \Illuminate\Database\Eloquent\Model
     * @param Builder<TModel> $query
     * @return Builder<TModel>
     */
    private function scope(Builder $query, Item $item): Builder
    {
        $query->where('tenant_id', $item->tenant_id)->where('item_id', $item->getKey());
        if ($item->organization_unit_id !== null) {
            $query->where('organization_unit_id', $item->organization_unit_id);
        }

        return $query;
    }
}
