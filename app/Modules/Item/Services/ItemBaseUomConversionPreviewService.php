<?php

declare(strict_types=1);

namespace Modules\Item\Services;

use Modules\Core\Services\DecimalMath;
use Modules\Inventory\Models\InventoryStockBalance;
use Modules\Inventory\Models\InventoryValuationLayer;
use Modules\Item\Models\Item;

final class ItemBaseUomConversionPreviewService
{
    public function __construct(
        private readonly DecimalMath $math,
        private readonly ItemBaseUomConversionValidator $validator,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function preview(
        Item $item,
        int $newBaseUomId,
        ?string $providedFactor = null,
        ?string $effectiveAt = null,
    ): array {
        $validation = $this->validator->validate($item, $newBaseUomId, $providedFactor, $effectiveAt);
        $factor = $validation['conversion_factor'];
        $rows = [];

        if (is_string($factor) && $this->math->compare($factor, '0') > 0) {
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
                        'after' => $this->math->mul((string) $balance->{$metric}, $factor),
                    ];
                }
                $rows[] = [
                    'area' => 'stock_balance',
                    'reference' => $balance->warehouse?->name ?: 'Stock balance #'.$balance->getKey(),
                    'metric' => 'average_cost',
                    'before' => $this->math->normalize((string) $balance->average_cost),
                    'after' => $this->math->div((string) $balance->average_cost, $factor),
                ];
            }

            $rows = [
                ...$rows,
                ...$this->flowSummary($item, 'reservation', 'inventory_reservations', 'quantity_remaining', $factor),
                ...$this->flowSummary($item, 'allocation', 'inventory_allocations', 'quantity_remaining', $factor),
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
                        'after' => $this->math->mul((string) $layer->{$metric}, $factor),
                    ];
                }
                $rows[] = [
                    'area' => 'valuation_layer',
                    'reference' => 'Layer #'.$layer->getKey(),
                    'metric' => 'unit_cost',
                    'before' => $this->math->normalize((string) $layer->unit_cost),
                    'after' => $this->math->div((string) $layer->unit_cost, $factor),
                ];
            }

        }

        return [
            'item' => $item,
            'old_base_uom' => $validation['old_uom'],
            'new_base_uom' => $validation['new_uom'],
            'conversion_factor' => $factor,
            'factor_source' => $validation['factor_source'],
            'effective_at' => $validation['effective_at'],
            'is_valid' => $validation['is_valid'],
            'blockers' => $validation['blockers'],
            'warnings' => $validation['warnings'],
            'affected_modules' => $validation['audit']['affected_modules'],
            'preview_rows' => $rows,
        ];
    }

    /**
     * @return list<array<string, string>>
     */
    private function flowSummary(Item $item, string $area, string $table, string $column, string $factor): array
    {
        $query = \Illuminate\Support\Facades\DB::table($table)
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
            'after' => $this->math->mul($before, $factor),
        ]];
    }

    private function scope($query, Item $item)
    {
        $query->where('tenant_id', $item->tenant_id)->where('item_id', $item->getKey());
        if ($item->organization_unit_id !== null) {
            $query->where('organization_unit_id', $item->organization_unit_id);
        }

        return $query;
    }
}
