<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $this->normalizeSafeLegacyStatuses();
        $this->assertNoUnsupportedLegacyStatusRows();
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
        $this->normalizeSafeDeadDebitNotes();

        DB::table('purchase_debit_notes')
            ->where('status', 'allocated')
            ->update(['status' => 'posted']);
    }

    private function normalizeSafeCancelledGoodsReceipts(): void
    {
        $safeIds = DB::table('goods_receipt_notes')
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

        if ($safeIds !== []) {
            DB::table('goods_receipt_notes')->whereIn('id', $safeIds)->update(['status' => 'draft']);
            DB::table('goods_receipt_note_lines')->whereIn('goods_receipt_note_id', $safeIds)->where('status', 'cancelled')->update(['status' => 'open']);
        }
    }

    private function normalizeSafeCancelledReceiptLines(): void
    {
        $safeReversedIds = DB::table('goods_receipt_note_lines')
            ->join('goods_receipt_notes', 'goods_receipt_note_lines.goods_receipt_note_id', '=', 'goods_receipt_notes.id')
            ->where('goods_receipt_note_lines.status', 'cancelled')
            ->where('goods_receipt_notes.status', 'reversed')
            ->pluck('goods_receipt_note_lines.id')
            ->map(static fn ($id): int => (int) $id)
            ->all();

        if ($safeReversedIds !== []) {
            DB::table('goods_receipt_note_lines')->whereIn('id', $safeReversedIds)->update(['status' => 'reversed']);
        }
    }

    private function normalizeSafeReversedReturns(): void
    {
        $safeIds = DB::table('purchase_returns')
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

        if ($safeIds !== []) {
            DB::table('purchase_returns')->whereIn('id', $safeIds)->update(['status' => 'cancelled']);
        }
    }

    private function normalizeSafeDeadDebitNotes(): void
    {
        $safeIds = DB::table('purchase_debit_notes')
            ->whereIn('status', ['cancelled', 'reversed'])
            ->whereRaw('allocated_amount <= 0')
            ->whereRaw('remaining_amount = amount')
            ->pluck('id')
            ->map(static fn ($id): int => (int) $id)
            ->all();

        foreach (array_chunk($safeIds, 500) as $chunk) {
            DB::table('purchase_debit_notes')
                ->whereIn('id', $chunk)
                ->whereNull('approved_at')
                ->update(['status' => 'draft']);

            DB::table('purchase_debit_notes')
                ->whereIn('id', $chunk)
                ->whereNotNull('approved_at')
                ->update(['status' => 'approved']);
        }
    }

    private function assertNoUnsupportedLegacyStatusRows(): void
    {
        $problems = [];
        $this->appendUnsupported($problems, 'goods_receipt_notes', ['cancelled'], 'Unposted/effect-free cancelled GRNs are mapped to draft automatically; posted or financially active cancelled GRNs must be manually remediated before deploying.');
        $this->appendUnsupported($problems, 'goods_receipt_note_lines', ['cancelled'], 'Cancelled lines under reversed GRNs are mapped to reversed automatically; all other cancelled GRN lines must be manually remediated.');
        $this->appendUnsupported($problems, 'purchase_returns', ['reversed'], 'Effect-free reversed Returns are mapped to cancelled automatically; posted or financially active reversed Returns need a supported reversal workflow before deployment.');
        $this->appendUnsupported($problems, 'purchase_debit_notes', ['cancelled', 'reversed'], 'Unallocated dead-state Debit Notes are mapped to draft/approved automatically; allocated or partially allocated rows must be remediated before deployment.');

        if ($problems !== []) {
            throw new RuntimeException('Cannot normalize Purchase lifecycle legacy statuses: '.implode(' | ', $problems));
        }
    }

    /**
     * @param  list<string>  $problems
     * @param  list<string>  $statuses
     */
    private function appendUnsupported(array &$problems, string $table, array $statuses, string $remediation): void
    {
        $rows = DB::table($table)
            ->select('status', DB::raw('COUNT(*) as aggregate'))
            ->whereIn('status', $statuses)
            ->groupBy('status')
            ->orderBy('status')
            ->get();

        foreach ($rows as $row) {
            $problems[] = "{$table}.status={$row->status} count={$row->aggregate}. {$remediation}";
        }
    }
};
