<?php

declare(strict_types=1);

namespace Modules\Item\Services;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Modules\Core\Services\DecimalMath;
use Modules\Item\Models\Item;

final class ItemBaseUomUsageAuditService
{
    public function __construct(private readonly DecimalMath $math) {}

    /**
     * @return array<string, mixed>
     */
    public function audit(Item $item): array
    {
        $references = [
            'inventory' => [
                'stock_balances' => $this->count('inventory_stock_balances', $item),
                'movements' => $this->count('inventory_movements', $item),
                'reservations' => $this->count('inventory_reservations', $item),
                'allocations' => $this->count('inventory_allocations', $item),
                'valuation_layers' => $this->count('inventory_valuation_layers', $item),
                'adjustments' => $this->count('inventory_adjustment_lines', $item),
                'transfers' => $this->count('inventory_transfer_lines', $item),
                'stock_counts' => $this->count('inventory_stock_count_lines', $item),
                'batches' => $this->count('inventory_batches', $item),
                'serial_numbers' => $this->count('inventory_serial_numbers', $item),
            ],
            'purchase' => [
                'purchase_orders' => $this->count('purchase_order_lines', $item),
                'goods_receipts' => $this->count('goods_receipt_note_lines', $item),
                'purchase_returns' => $this->count('purchase_return_lines', $item),
            ],
            'vehicle_service' => [
                'job_lines' => $this->count('vehicle_service_job_lines', $item),
            ],
            'invoice' => [
                'invoice_lines' => $this->count('invoice_lines', $item),
            ],
            'master_data' => [
                'item_units' => $this->count('item_units', $item),
                'item_prices' => $this->count('item_prices', $item),
                'supplier_mappings' => $this->count('supplier_item_mappings', $item),
                'bundle_references' => $this->bundleReferenceCount($item),
            ],
        ];

        $affectedModules = [];
        foreach ($references as $module => $moduleReferences) {
            $count = array_sum($moduleReferences);
            if ($count > 0) {
                $affectedModules[] = [
                    'module' => $module,
                    'count' => $count,
                    'references' => $moduleReferences,
                ];
            }
        }

        $usageCount = array_sum(array_map(
            static fn (array $counts): int => array_sum($counts),
            array_intersect_key($references, array_flip(['inventory', 'purchase', 'vehicle_service', 'invoice'])),
        ));

        $blockers = $this->operationalBlockers($item);
        $warnings = [];
        if ($references['master_data']['item_units'] > 0) {
            $warnings[] = 'Item-unit conversion factors will be rebased to the new base UOM.';
        }
        if (($references['master_data']['item_prices'] + $references['master_data']['supplier_mappings'] + $references['master_data']['bundle_references']) > 0) {
            $warnings[] = 'Explicit price, supplier, and bundle UOM references remain unchanged and should be reviewed after conversion.';
        }
        if ($references['inventory']['movements'] > 0) {
            $warnings[] = 'Historical inventory movements remain unchanged and retain their UOM snapshot.';
        }

        return [
            'item' => $item,
            'has_usage' => $usageCount > 0,
            'can_direct_edit' => $usageCount === 0,
            'usage_count' => $usageCount,
            'affected_modules' => $affectedModules,
            'blockers' => $blockers,
            'warnings' => $warnings,
        ];
    }

    /**
     * @return list<array{code: string, message: string, count: int}>
     */
    private function operationalBlockers(Item $item): array
    {
        $blockers = [];

        $activeReservations = $this->scoped(DB::table('inventory_reservations'), $item)
            ->whereIn('status', ['active', 'partially_allocated'])
            ->where('quantity_remaining', '>', 0)
            ->where(function (Builder $query) use ($item): void {
                $query->whereNull('base_uom_id')->orWhere('base_uom_id', '!=', $item->base_uom_id);
            })
            ->count();
        $this->addBlocker($blockers, 'unsafe_reservations', 'Open reservations without the old base-UOM snapshot cannot be converted safely.', $activeReservations);

        $activeAllocations = $this->scoped(DB::table('inventory_allocations'), $item)
            ->where('status', 'active')
            ->where('quantity_remaining', '>', 0)
            ->where(function (Builder $query) use ($item): void {
                $query->whereNull('base_uom_id')->orWhere('base_uom_id', '!=', $item->base_uom_id);
            })
            ->count();
        $this->addBlocker($blockers, 'unsafe_allocations', 'Open allocations without the old base-UOM snapshot cannot be converted safely.', $activeAllocations);

        $draftMovements = $this->scoped(DB::table('inventory_movements'), $item)
            ->where('status', 'draft')
            ->count();
        $this->addBlocker($blockers, 'draft_movements', 'Draft inventory movements must be posted or cancelled before conversion.', $draftMovements);

        $unsafeAdjustments = $this->scopedJoin(
            DB::table('inventory_adjustment_lines as lines')
                ->join('inventory_adjustments as parents', 'parents.id', '=', 'lines.inventory_adjustment_id'),
            $item,
            'lines',
        )->whereIn('parents.status', ['draft', 'approved'])->count();
        $this->addBlocker($blockers, 'open_adjustments', 'Open inventory adjustments must be completed or cancelled before conversion.', $unsafeAdjustments);

        $unsafeTransfers = $this->scopedJoin(
            DB::table('inventory_transfer_lines as lines')
                ->join('inventory_transfers as parents', 'parents.id', '=', 'lines.inventory_transfer_id'),
            $item,
            'lines',
        )->whereIn('parents.status', ['pending', 'draft', 'approved', 'dispatched', 'in_transit'])->count();
        $this->addBlocker($blockers, 'open_transfers', 'Open inventory transfers must be completed or cancelled before conversion.', $unsafeTransfers);

        $unsafeStockCounts = $this->scopedJoin(
            DB::table('inventory_stock_count_lines as lines')
                ->join('inventory_stock_counts as parents', 'parents.id', '=', 'lines.inventory_stock_count_id'),
            $item,
            'lines',
        )->whereIn('parents.status', ['draft', 'approved'])->count();
        $this->addBlocker($blockers, 'open_stock_counts', 'Open inventory stock counts must be posted or cancelled before conversion.', $unsafeStockCounts);

        $unsafePurchaseOrders = $this->scopedJoin(
            DB::table('purchase_order_lines as lines')
                ->join('purchase_orders as parents', 'parents.id', '=', 'lines.purchase_order_id'),
            $item,
            'lines',
        )->whereNotIn('parents.status', ['closed', 'cancelled'])
            ->where('lines.remaining_receivable_quantity', '>', 0)
            ->where(function (Builder $query): void {
                $query->whereNull('lines.ordered_uom_id')->orWhereNull('lines.base_uom_id');
            })
            ->count();
        $this->addBlocker($blockers, 'unsafe_purchase_orders', 'Open purchase lines without UOM snapshots must be completed or cancelled.', $unsafePurchaseOrders);

        $unsafeReceipts = $this->scopedJoin(
            DB::table('goods_receipt_note_lines as lines')
                ->join('goods_receipt_notes as parents', 'parents.id', '=', 'lines.goods_receipt_note_id'),
            $item,
            'lines',
        )->whereNotIn('parents.status', ['returned', 'cancelled', 'reversed'])
            ->where('lines.remaining_quantity', '>', 0)
            ->where(function (Builder $query): void {
                $query->whereNull('lines.ordered_uom_id')->orWhereNull('lines.base_uom_id');
            })
            ->count();
        $this->addBlocker($blockers, 'unsafe_goods_receipts', 'GRN lines without UOM snapshots must be resolved before conversion.', $unsafeReceipts);

        $unsafePurchaseReturns = $this->scopedJoin(
            DB::table('purchase_return_lines as lines')
                ->join('purchase_returns as parents', 'parents.id', '=', 'lines.purchase_return_id'),
            $item,
            'lines',
        )->whereIn('parents.status', ['draft', 'approved'])
            ->whereNull('lines.uom_id')
            ->count();
        $this->addBlocker($blockers, 'unsafe_purchase_returns', 'Open purchase return lines without a UOM snapshot must be resolved.', $unsafePurchaseReturns);

        $unsafeServiceLines = $this->scopedJoin(
            DB::table('vehicle_service_job_lines as lines')
                ->join('vehicle_service_jobs as parents', 'parents.id', '=', 'lines.vehicle_service_job_id'),
            $item,
            'lines',
        )->where('lines.is_inventory_tracked', true)
            ->whereNull('lines.inventory_movement_id')
            ->whereNull('lines.uom_id')
            ->whereNotIn('parents.status', ['invoiced', 'partially_paid', 'paid', 'cancelled'])
            ->count();
        $this->addBlocker($blockers, 'unsafe_vehicle_service_lines', 'Unissued vehicle-service inventory lines without a UOM snapshot must be resolved.', $unsafeServiceLines);

        $implicitBundles = DB::table('item_bundles')
            ->where('tenant_id', $item->tenant_id)
            ->where('child_item_id', $item->getKey())
            ->whereNull('uom_id');
        if ($item->organization_unit_id !== null) {
            $implicitBundles->where('organization_unit_id', $item->organization_unit_id);
        }
        $this->addBlocker($blockers, 'implicit_bundle_quantities', 'Bundle quantities using this item without an explicit UOM must be assigned a UOM before conversion.', $implicitBundles->count());

        $implicitSupplierMappings = $this->scoped(DB::table('supplier_item_mappings'), $item)
            ->whereNull('default_purchase_uom_id')
            ->where('is_active', true)
            ->count();
        $this->addBlocker($blockers, 'implicit_supplier_uom', 'Active supplier item mappings without an explicit purchase UOM must be updated before conversion.', $implicitSupplierMappings);

        $invalidBalances = $this->scoped(DB::table('inventory_stock_balances'), $item)
            ->get([
                'quantity_on_hand',
                'quantity_reserved',
                'quantity_allocated',
                'quantity_available',
                'quantity_returned',
                'quantity_damaged',
                'quantity_quarantine',
                'quantity_expired',
                'quantity_scrapped',
            ])
            ->filter(function ($row): bool {
                foreach ([
                    'quantity_on_hand',
                    'quantity_reserved',
                    'quantity_allocated',
                    'quantity_available',
                    'quantity_returned',
                    'quantity_damaged',
                    'quantity_quarantine',
                    'quantity_expired',
                    'quantity_scrapped',
                ] as $column) {
                    if ($this->math->isNegative((string) $row->{$column})) {
                        return true;
                    }
                }

                $expected = $this->math->sub(
                    (string) $row->quantity_on_hand,
                    $this->math->sum([
                        (string) $row->quantity_reserved,
                        (string) $row->quantity_allocated,
                        (string) $row->quantity_returned,
                        (string) $row->quantity_damaged,
                        (string) $row->quantity_quarantine,
                        (string) $row->quantity_expired,
                        (string) $row->quantity_scrapped,
                    ]),
                );

                return $this->math->compare($expected, (string) $row->quantity_available) !== 0;
            })
            ->count();
        $this->addBlocker($blockers, 'invalid_stock_state', 'Stock balances contain negative or inconsistent quantities.', $invalidBalances);

        $invalidLayers = $this->scoped(DB::table('inventory_valuation_layers'), $item)
            ->where('status', 'open')
            ->get(['original_quantity', 'remaining_quantity', 'unit_cost', 'remaining_value'])
            ->filter(fn ($row): bool => $this->math->isNegative((string) $row->original_quantity)
                || $this->math->isNegative((string) $row->remaining_quantity)
                || $this->math->isNegative((string) $row->unit_cost)
                || $this->math->isNegative((string) $row->remaining_value))
            ->count();
        $this->addBlocker($blockers, 'invalid_valuation_state', 'Open valuation layers contain negative quantities, costs, or values.', $invalidLayers);

        return $blockers;
    }

    private function count(string $table, Item $item): int
    {
        return $this->scoped(DB::table($table), $item)->count();
    }

    private function bundleReferenceCount(Item $item): int
    {
        $query = DB::table('item_bundles')->where('tenant_id', $item->tenant_id)
            ->where(function (Builder $query) use ($item): void {
                $query->where('parent_item_id', $item->getKey())
                    ->orWhere('child_item_id', $item->getKey());
            });

        if ($item->organization_unit_id !== null) {
            $query->where('organization_unit_id', $item->organization_unit_id);
        }

        return $query->count();
    }

    private function scoped(Builder $query, Item $item, string $prefix = ''): Builder
    {
        $column = $prefix === '' ? '' : $prefix.'.';
        $query->where($column.'tenant_id', $item->tenant_id)
            ->where($column.'item_id', $item->getKey());
        if ($item->organization_unit_id !== null) {
            $query->where($column.'organization_unit_id', $item->organization_unit_id);
        }

        return $query;
    }

    private function scopedJoin(Builder $query, Item $item, string $prefix): Builder
    {
        return $this->scoped($query, $item, $prefix);
    }

    /**
     * @param  list<array{code: string, message: string, count: int}>  $blockers
     */
    private function addBlocker(array &$blockers, string $code, string $message, int $count): void
    {
        if ($count > 0) {
            $blockers[] = compact('code', 'message', 'count');
        }
    }
}
