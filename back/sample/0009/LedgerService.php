<?php

namespace App\Services\Inventory;

use App\Models\StockLedgerEntry;
use App\Models\StockPosition;
use App\Models\DocumentSequence;
use Illuminate\Support\Facades\DB;

/**
 * LedgerService
 *
 * Responsible for writing immutable stock ledger entries.
 * The ledger is the single source of truth for all inventory movements.
 *
 * RULES:
 *  1. Entries are NEVER updated or deleted after insert.
 *  2. Every movement (in or out) produces at least one entry.
 *  3. Transfers produce two entries: OUT from source, IN to destination.
 *  4. Reference numbers are sequential and unique per organization.
 *  5. Running balance (quantity_before / quantity_after) is maintained atomically.
 */
class LedgerService
{
    public function write(
        array         $params,
        StockPosition $position,
        string        $valuationMethod,
        string        $direction,
    ): StockLedgerEntry {
        $qty    = $params['quantity'];
        $before = $position->qty_on_hand + ($direction === 'IN' ? -$qty : $qty); // reverse to get "before"
        $after  = $position->qty_on_hand;

        return StockLedgerEntry::create([
            'organization_id'       => $params['organization_id'],
            'reference_number'      => $this->nextReference($params['organization_id']),
            'product_id'            => $params['product_id'],
            'product_variant_id'    => $params['product_variant_id'] ?? null,
            'warehouse_id'          => $params['warehouse_id'],
            'storage_location_id'   => $params['storage_location_id'] ?? null,
            'lot_id'                => $params['lot_id'] ?? null,
            'batch_id'              => $params['batch_id'] ?? null,
            'serial_number_id'      => $params['serial_number_id'] ?? null,
            'uom_id'                => $params['uom_id'] ?? null,

            'movement_type'         => $params['movement_type'],
            'direction'             => $direction,
            'quantity'              => $qty,
            'quantity_before'       => $before,
            'quantity_after'        => $after,

            'valuation_method'      => $valuationMethod,
            'unit_cost'             => $params['unit_cost'] ?? 0,
            'total_cost'            => $params['total_cost'] ?? (($params['unit_cost'] ?? 0) * $qty),
            'average_cost_before'   => $params['average_cost_before'] ?? null,
            'average_cost_after'    => $params['average_cost_after'] ?? null,

            'source_document_type'  => $params['source_document_type'] ?? null,
            'source_document_id'    => $params['source_document_id'] ?? null,
            'source_document_number'=> $params['source_document_number'] ?? null,
            'source_line_type'      => $params['source_line_type'] ?? null,
            'source_line_id'        => $params['source_line_id'] ?? null,

            'reason_code'           => $params['reason_code'] ?? null,
            'notes'                 => $params['notes'] ?? null,
            'created_by'            => auth()->id(),
            'movement_date'         => $params['movement_date'] ?? now(),
        ]);
    }

    /**
     * Generate the next sequential reference number for the organization.
     * Format: JRN-{YEAR}-{NNNNNN}  e.g. JRN-2024-000142
     */
    private function nextReference(int $organizationId): string
    {
        return DB::transaction(function () use ($organizationId) {
            $seq = DocumentSequence::where('organization_id', $organizationId)
                ->where('document_type', 'journal')
                ->lockForUpdate()
                ->firstOrCreate(
                    ['organization_id' => $organizationId, 'document_type' => 'journal'],
                    ['prefix' => 'JRN', 'next_number' => 1, 'padding' => 6, 'include_year' => true]
                );

            $number = str_pad($seq->next_number, $seq->padding, '0', STR_PAD_LEFT);
            $year   = $seq->include_year ? ('-' . now()->year) : '';
            $ref    = "{$seq->prefix}{$year}-{$number}";

            $seq->increment('next_number');

            return $ref;
        });
    }

    /**
     * Replay ledger for a product/warehouse to rebuild running balances.
     * Use this for data integrity checks or after bulk imports.
     */
    public function replayBalances(int $productId, int $warehouseId): void
    {
        $entries = StockLedgerEntry::where('product_id', $productId)
            ->where('warehouse_id', $warehouseId)
            ->orderBy('movement_date')
            ->orderBy('id')
            ->get();

        $balance = 0;

        foreach ($entries as $entry) {
            $before  = $balance;
            $balance = $entry->direction === 'IN'
                ? $balance + $entry->quantity
                : $balance - $entry->quantity;

            // Only update if values are wrong (avoids unnecessary writes)
            if ($entry->quantity_before != $before || $entry->quantity_after != $balance) {
                $entry->updateQuietly([
                    'quantity_before' => $before,
                    'quantity_after'  => $balance,
                ]);
            }
        }
    }

    /**
     * Get the complete movement history for a product across all warehouses,
     * supporting full forward and backward traceability.
     */
    public function getHistory(
        int     $productId,
        ?int    $warehouseId  = null,
        ?int    $lotId        = null,
        ?int    $batchId      = null,
        ?string $fromDate     = null,
        ?string $toDate       = null,
    ): \Illuminate\Database\Eloquent\Collection {
        return StockLedgerEntry::where('product_id', $productId)
            ->when($warehouseId, fn ($q) => $q->where('warehouse_id', $warehouseId))
            ->when($lotId,       fn ($q) => $q->where('lot_id', $lotId))
            ->when($batchId,     fn ($q) => $q->where('batch_id', $batchId))
            ->when($fromDate,    fn ($q) => $q->where('movement_date', '>=', $fromDate))
            ->when($toDate,      fn ($q) => $q->where('movement_date', '<=', $toDate))
            ->with(['product', 'warehouse', 'lot', 'batch', 'createdBy'])
            ->orderBy('movement_date', 'desc')
            ->orderBy('id', 'desc')
            ->get();
    }
}
