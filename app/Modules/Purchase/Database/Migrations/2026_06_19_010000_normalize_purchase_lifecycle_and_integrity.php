<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->abortIfUnsupportedStatusesExist();
        $this->normalizeLifecycleStatuses();
        $this->addIndexes();
        $this->addChecks();
    }

    public function down(): void
    {
        $this->dropChecks();

        Schema::table('purchase_order_lines', function (Blueprint $table): void {
            $table->dropIndex('purchase_order_lines_balance_idx');
        });
        Schema::table('goods_receipt_note_lines', function (Blueprint $table): void {
            $table->dropIndex('goods_receipt_note_lines_balance_idx');
        });
        Schema::table('purchase_debit_notes', function (Blueprint $table): void {
            $table->dropIndex('purchase_debit_notes_allocation_idx');
        });
    }

    private function abortIfUnsupportedStatusesExist(): void
    {
        $unsupported = [
            'goods_receipt_notes' => ['cancelled'],
            'goods_receipt_note_lines' => ['cancelled'],
            'purchase_returns' => ['reversed'],
            'purchase_debit_notes' => ['cancelled', 'reversed'],
        ];

        foreach ($unsupported as $table => $statuses) {
            $rows = DB::table($table)
                ->select('status', DB::raw('COUNT(*) as aggregate'))
                ->whereIn('status', $statuses)
                ->groupBy('status')
                ->orderBy('status')
                ->get();
            if ($rows->isNotEmpty()) {
                $details = $rows
                    ->map(fn ($row): string => "{$row->status}={$row->aggregate}")
                    ->implode(', ');

                throw new RuntimeException("Cannot normalize Purchase lifecycle: {$table} still contains unsupported statuses ({$details}) after legacy preflight remediation.");
            }
        }
    }

    private function normalizeLifecycleStatuses(): void
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

        DB::table('goods_receipt_notes')
            ->whereIn('status', [
                'partially_returned',
                'returned',
                'partially_invoiced',
                'invoiced',
            ])
            ->update(['status' => 'posted']);

        DB::table('purchase_debit_notes')
            ->where('status', 'allocated')
            ->update(['status' => 'posted']);

        DB::table('purchase_order_lines')
            ->whereIn('status', ['partially_received', 'received', 'partially_invoiced', 'invoiced'])
            ->update(['status' => 'open']);

        DB::table('goods_receipt_note_lines')
            ->whereIn('status', ['partially_returned', 'returned', 'partially_invoiced', 'invoiced'])
            ->update(['status' => 'posted']);
    }

    private function addIndexes(): void
    {
        Schema::table('purchase_order_lines', function (Blueprint $table): void {
            $table->index(['purchase_order_id', 'received_quantity', 'invoiced_quantity', 'returned_quantity'], 'purchase_order_lines_balance_idx');
        });
        Schema::table('goods_receipt_note_lines', function (Blueprint $table): void {
            $table->index(['goods_receipt_note_id', 'accepted_quantity', 'invoiced_quantity', 'returned_quantity'], 'goods_receipt_note_lines_balance_idx');
        });
        Schema::table('purchase_debit_notes', function (Blueprint $table): void {
            $table->index(['status', 'allocated_amount', 'remaining_amount'], 'purchase_debit_notes_allocation_idx');
        });
    }

    private function addChecks(): void
    {
        if (! in_array(DB::getDriverName(), ['mysql', 'pgsql'], true)) {
            return;
        }

        foreach ($this->checks() as [$table, $name, $expression]) {
            DB::statement("ALTER TABLE {$table} ADD CONSTRAINT {$name} CHECK ({$expression})");
        }
    }

    private function dropChecks(): void
    {
        if (! in_array(DB::getDriverName(), ['mysql', 'pgsql'], true)) {
            return;
        }

        foreach ($this->checks() as [$table, $name]) {
            if (DB::getDriverName() === 'pgsql') {
                DB::statement("ALTER TABLE {$table} DROP CONSTRAINT IF EXISTS {$name}");

                continue;
            }

            $this->dropMysqlCheck($table, $name);
        }
    }

    /**
     * @return list<array{0: string, 1: string, 2: string}>
     */
    private function checks(): array
    {
        return [
            ['purchase_orders', 'purchase_orders_status_chk', "status IN ('draft','pending_approval','approved','closed','cancelled')"],
            ['purchase_order_lines', 'purchase_order_lines_status_chk', "status IN ('open','closed','cancelled')"],
            ['purchase_order_lines', 'purchase_order_lines_quantities_chk', 'ordered_quantity >= 0 AND received_quantity >= 0 AND invoiced_quantity >= 0 AND returned_quantity >= 0 AND cancelled_quantity >= 0 AND received_quantity <= ordered_quantity - cancelled_quantity AND invoiced_quantity <= ordered_quantity - cancelled_quantity AND returned_quantity <= received_quantity'],
            ['purchase_order_lines', 'purchase_order_lines_money_chk', 'unit_price >= 0 AND line_subtotal >= 0 AND discount_rate >= 0 AND discount_rate <= 100 AND tax_rate >= 0 AND tax_rate <= 100 AND charge_rate >= 0 AND charge_rate <= 100 AND discount_amount >= 0 AND tax_amount >= 0 AND charge_amount >= 0 AND line_total >= 0'],
            ['goods_receipt_notes', 'goods_receipt_notes_status_chk', "status IN ('draft','posted','reversed')"],
            ['goods_receipt_note_lines', 'goods_receipt_note_lines_status_chk', "status IN ('open','posted','reversed')"],
            ['goods_receipt_note_lines', 'goods_receipt_note_lines_quantities_chk', 'received_quantity >= 0 AND accepted_quantity >= 0 AND rejected_quantity >= 0 AND invoiced_quantity >= 0 AND returned_quantity >= 0 AND accepted_quantity + rejected_quantity = received_quantity AND invoiced_quantity <= accepted_quantity AND returned_quantity <= accepted_quantity'],
            ['goods_receipt_note_lines', 'goods_receipt_note_lines_money_chk', 'unit_price >= 0 AND line_subtotal >= 0 AND discount_amount >= 0 AND tax_amount >= 0 AND charge_amount >= 0 AND line_total >= 0'],
            ['purchase_returns', 'purchase_returns_status_chk', "status IN ('draft','approved','posted','cancelled')"],
            ['purchase_return_lines', 'purchase_return_lines_quantities_chk', 'returned_quantity > 0 AND source_quantity >= 0 AND previously_returned_quantity >= 0 AND remaining_quantity >= 0'],
            ['purchase_return_lines', 'purchase_return_lines_money_chk', 'unit_price >= 0 AND base_amount >= 0 AND discount_amount >= 0 AND tax_amount >= 0 AND charge_amount >= 0 AND line_total >= 0'],
            ['purchase_return_adjustment_allocations', 'purchase_return_adjustment_allocations_amounts_chk', 'source_amount >= 0 AND previously_returned_amount >= 0 AND returned_amount >= 0 AND remaining_amount >= 0'],
            ['purchase_debit_notes', 'purchase_debit_notes_status_chk', "status IN ('draft','approved','posted')"],
            ['purchase_debit_notes', 'purchase_debit_notes_amounts_chk', 'amount >= 0 AND allocated_amount >= 0 AND remaining_amount >= 0 AND allocated_amount <= amount AND remaining_amount <= amount AND allocated_amount + remaining_amount = amount'],
        ];
    }

    private function dropMysqlCheck(string $table, string $name): void
    {
        try {
            DB::statement("ALTER TABLE {$table} DROP CHECK {$name}");
        } catch (Throwable) {
            DB::statement("ALTER TABLE {$table} DROP CONSTRAINT {$name}");
        }
    }
};
