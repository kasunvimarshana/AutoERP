<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $this->assertNoUnsupportedLegacyStatusRows();

        DB::transaction(function (): void {
            $this->normalizeSafeLegacyStatuses();
        });
    }

    public function down(): void
    {
        // Data normalization is intentionally not reversible: legacy progress
        // and dead workflow statuses cannot be reconstructed safely.
    }

    private function normalizeSafeLegacyStatuses(): void
    {
        DB::table('purchase_orders')
            ->whereIn('status', [
                'partially_received',
                'received',
                'partially_invoiced',
                'invoiced',
                'partially_returned',
                'returned',
            ])
            ->update(['status' => 'approved']);

        DB::table('purchase_order_lines')
            ->whereIn('status', ['partially_received', 'received', 'partially_invoiced', 'invoiced'])
            ->update(['status' => 'open']);

        DB::table('goods_receipt_notes')
            ->whereIn('status', [
                'partially_returned',
                'returned',
                'partially_invoiced',
                'invoiced',
            ])
            ->update(['status' => 'posted']);

        DB::table('goods_receipt_note_lines')
            ->whereIn('status', ['partially_returned', 'returned', 'partially_invoiced', 'invoiced'])
            ->update(['status' => 'posted']);

        $this->normalizeSafeCancelledGoodsReceipts();
        $this->normalizeSafeCancelledReceiptLines();
        $this->normalizeSafeReversedReturns();
        DB::table('purchase_debit_notes')
            ->where('status', 'allocated')
            ->update(['status' => 'posted']);
    }

    private function normalizeSafeCancelledGoodsReceipts(): void
    {
        $safeIds = $this->safeCancelledGoodsReceiptIds();

        if ($safeIds !== []) {
            DB::table('goods_receipt_notes')->whereIn('id', $safeIds)->update(['status' => 'draft']);
            DB::table('goods_receipt_note_lines')->whereIn('goods_receipt_note_id', $safeIds)->where('status', 'cancelled')->update(['status' => 'open']);
        }
    }

    /**
     * @return list<int>
     */
    private function safeCancelledGoodsReceiptIds(): array
    {
        return DB::table('goods_receipt_notes')
            ->where('status', 'cancelled')
            ->whereNull('posted_at')
            ->whereNull('reversed_at')
            ->whereNotExists(function ($query): void {
                $query->selectRaw('1')
                    ->from('goods_receipt_note_lines')
                    ->whereColumn('goods_receipt_note_lines.goods_receipt_note_id', 'goods_receipt_notes.id')
                    ->where(function ($line): void {
                        $line->whereNotNull('inventory_movement_id')
                            ->orWhereRaw('invoiced_quantity > 0')
                            ->orWhereRaw('returned_quantity > 0');
                    });
            })
            ->pluck('id')
            ->map(static fn ($id): int => (int) $id)
            ->all();
    }

    private function normalizeSafeCancelledReceiptLines(): void
    {
        $safeReversedIds = $this->safeCancelledReceiptLineIdsForReversedReceipts();

        if ($safeReversedIds !== []) {
            DB::table('goods_receipt_note_lines')->whereIn('id', $safeReversedIds)->update(['status' => 'reversed']);
        }
    }

    /**
     * @return list<int>
     */
    private function safeCancelledReceiptLineIdsForReversedReceipts(): array
    {
        return DB::table('goods_receipt_note_lines')
            ->join('goods_receipt_notes', 'goods_receipt_note_lines.goods_receipt_note_id', '=', 'goods_receipt_notes.id')
            ->where('goods_receipt_note_lines.status', 'cancelled')
            ->where('goods_receipt_notes.status', 'reversed')
            ->pluck('goods_receipt_note_lines.id')
            ->map(static fn ($id): int => (int) $id)
            ->all();
    }

    /**
     * @return list<int>
     */
    private function safeCancelledReceiptLineIdsForSafeCancelledReceipts(): array
    {
        $safeReceiptIds = $this->safeCancelledGoodsReceiptIds();
        if ($safeReceiptIds === []) {
            return [];
        }

        return DB::table('goods_receipt_note_lines')
            ->whereIn('goods_receipt_note_id', $safeReceiptIds)
            ->where('status', 'cancelled')
            ->pluck('id')
            ->map(static fn ($id): int => (int) $id)
            ->all();
    }

    private function normalizeSafeReversedReturns(): void
    {
        $safeIds = $this->safeReversedReturnIds();

        if ($safeIds !== []) {
            DB::table('purchase_returns')->whereIn('id', $safeIds)->update(['status' => 'cancelled']);
        }
    }

    /**
     * @return list<int>
     */
    private function safeReversedReturnIds(): array
    {
        return DB::table('purchase_returns')
            ->where('status', 'reversed')
            ->whereNull('posted_at')
            ->whereNull('debit_note_id')
            ->whereNotExists(function ($query): void {
                $query->selectRaw('1')
                    ->from('purchase_return_lines')
                    ->whereColumn('purchase_return_lines.purchase_return_id', 'purchase_returns.id')
                    ->whereNotNull('inventory_movement_id');
            })
            ->pluck('id')
            ->map(static fn ($id): int => (int) $id)
            ->all();
    }

    private function assertNoUnsupportedLegacyStatusRows(): void
    {
        $problems = [];
        $this->appendUnsupported($problems, 'goods_receipt_notes', ['cancelled'], 'Unposted/effect-free cancelled GRNs are mapped to draft automatically; posted or financially active cancelled GRNs must be manually remediated before deploying.', $this->safeCancelledGoodsReceiptIds());
        $this->appendUnsupported($problems, 'goods_receipt_note_lines', ['cancelled'], 'Cancelled lines under reversed GRNs are mapped to reversed, and lines under effect-free cancelled GRNs are mapped to open; all other cancelled GRN lines must be manually remediated.', array_merge(
            $this->safeCancelledReceiptLineIdsForReversedReceipts(),
            $this->safeCancelledReceiptLineIdsForSafeCancelledReceipts(),
        ));
        $this->appendUnsupported($problems, 'purchase_returns', ['reversed'], 'Effect-free reversed Returns are mapped to cancelled automatically; posted or financially active reversed Returns need a supported reversal workflow before deployment.', $this->safeReversedReturnIds());
        $this->appendUnsupported($problems, 'purchase_debit_notes', ['cancelled', 'reversed'], 'Dead-state Debit Notes are financial documents and are not reactivated automatically. Remediate, archive, or map them with an audited project-specific recovery before deploying.');

        if ($problems !== []) {
            throw new RuntimeException('Cannot normalize Purchase lifecycle legacy statuses: '.implode(' | ', $problems));
        }
    }

    /**
     * @param  list<string>  $problems
     * @param  list<string>  $statuses
     * @param  list<int>  $safeIds
     */
    private function appendUnsupported(array &$problems, string $table, array $statuses, string $remediation, array $safeIds = []): void
    {
        foreach ($statuses as $status) {
            $query = DB::table($table)->where('status', $status);
            if ($safeIds !== []) {
                $query->whereNotIn('id', array_values(array_unique($safeIds)));
            }

            $count = (clone $query)->count();
            if ($count < 1) {
                continue;
            }

            $sampleIds = (clone $query)
                ->orderBy('id')
                ->limit(10)
                ->pluck('id')
                ->map(static fn ($id): string => (string) $id)
                ->implode(',');
            $problems[] = "{$table}.status={$status} count={$count} sample_ids=[{$sampleIds}]. {$remediation}";
        }
    }
};
