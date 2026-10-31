<?php

declare(strict_types=1);

namespace Modules\Inventory\Services;

use Illuminate\Support\Facades\DB;
use Modules\Inventory\Models\InventoryMovement;
use Modules\Inventory\Models\InventoryValuationLayer;

final class InventorySourceDocumentResolver
{
    private const DOCUMENTS = [
        'goods_receipt_note' => ['goods_receipt_notes', 'grn_number', '/purchase/goods-receipts/'],
        'goods_receipt_note_reversal' => ['goods_receipt_notes', 'grn_number', '/purchase/goods-receipts/'],
        'sale' => ['sales', 'sale_number', '/selling/sales/'],
        'sale_return' => ['sale_returns', 'return_number', null],
        'vehicle_service_job' => ['vehicle_service_jobs', 'job_number', '/vehicle-service/jobs/'],
        'purchase_return' => ['purchase_returns', 'return_number', '/purchase/returns/'],
        'inventory_adjustment' => ['inventory_adjustments', 'adjustment_number', null],
        'inventory_transfer' => ['inventory_transfers', 'transfer_number', null],
        'inventory_movement' => ['inventory_movements', 'movement_number', null],
    ];

    /**
     * @param  array<int, InventoryMovement|InventoryValuationLayer>  $sources
     * @return array<string, array<int, array{label: string, url: ?string}>>
     */
    public function resolve(array $sources, int $tenantId, ?int $organizationUnitId): array
    {
        $idsByType = [];
        foreach ($sources as $source) {
            if ($source->source_type !== null && $source->source_id !== null) {
                $idsByType[(string) $source->source_type][] = (int) $source->source_id;
            }
        }

        $documents = [];
        foreach (self::DOCUMENTS as $type => [$table, $numberColumn, $route]) {
            $ids = array_unique($idsByType[$type] ?? []);
            if ($ids === []) {
                continue;
            }

            $query = DB::table($table)->where('tenant_id', $tenantId)->whereIn('id', $ids);
            if ($organizationUnitId !== null) {
                $query->where(function ($scope) use ($organizationUnitId): void {
                    $scope->whereNull('organization_unit_id')->orWhere('organization_unit_id', $organizationUnitId);
                });
            }

            foreach ($query->get(['id', $numberColumn]) as $record) {
                $documents[$type][(int) $record->id] = [
                    'label' => (string) $record->{$numberColumn},
                    'url' => $route === null ? null : $route.$record->id,
                ];
            }
        }

        return $documents;
    }
}
