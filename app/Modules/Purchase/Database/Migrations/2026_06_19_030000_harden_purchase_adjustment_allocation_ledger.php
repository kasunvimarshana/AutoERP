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
        if (! Schema::hasTable('purchase_adjustment_allocations')) {
            return;
        }

        $this->preflight();

        Schema::table('purchase_adjustment_allocations', function (Blueprint $table): void {
            if (! Schema::hasColumn('purchase_adjustment_allocations', 'entry_type')) {
                $table->string('entry_type', 30)->default('allocation');
            }
            if (! Schema::hasColumn('purchase_adjustment_allocations', 'reversal_of_id')) {
                $table->unsignedBigInteger('reversal_of_id')->nullable();
            }
            if (! Schema::hasColumn('purchase_adjustment_allocations', 'correlation_key')) {
                $table->string('correlation_key', 160)->nullable();
            }
            if (! Schema::hasColumn('purchase_adjustment_allocations', 'event_type')) {
                $table->string('event_type', 80)->nullable();
            }
        });

        DB::table('purchase_adjustment_allocations')
            ->whereNull('entry_type')
            ->update(['entry_type' => 'allocation']);

        Schema::table('purchase_adjustment_allocations', function (Blueprint $table): void {
            $table->foreign('reversal_of_id', 'purchase_adj_alloc_reversal_fk')
                ->references('id')
                ->on('purchase_adjustment_allocations')
                ->restrictOnDelete();
            $table->unique('correlation_key', 'purchase_adj_alloc_correlation_uk');
            $table->unique(['reversal_of_id', 'entry_type'], 'purchase_adj_alloc_one_reversal_uk');
            $table->index(['purchase_header_adjustment_id', 'entry_type', 'stage'], 'purchase_adj_alloc_effective_stage_idx');
            $table->index(['target_type', 'target_id', 'entry_type'], 'purchase_adj_alloc_target_effective_idx');
            $table->index(['event_type', 'entry_type'], 'purchase_adj_alloc_event_idx');
        });

        $this->addChecks();
    }

    public function down(): void
    {
        if (! Schema::hasTable('purchase_adjustment_allocations')) {
            return;
        }

        $this->dropChecks();

        Schema::table('purchase_adjustment_allocations', function (Blueprint $table): void {
            $this->dropIndexIfExists($table, 'purchase_adj_alloc_event_idx');
            $this->dropIndexIfExists($table, 'purchase_adj_alloc_target_effective_idx');
            $this->dropIndexIfExists($table, 'purchase_adj_alloc_effective_stage_idx');
            $this->dropIndexIfExists($table, 'purchase_adj_alloc_one_reversal_uk');
            $this->dropIndexIfExists($table, 'purchase_adj_alloc_correlation_uk');
            $table->dropForeign('purchase_adj_alloc_reversal_fk');
            $table->dropColumn(['entry_type', 'reversal_of_id', 'correlation_key', 'event_type']);
        });
    }

    private function preflight(): void
    {
        $negative = DB::table('purchase_adjustment_allocations')
            ->where(function ($query): void {
                foreach (['source_amount', 'allocated_amount', 'recognized_at_grn_amount', 'recognized_at_invoice_amount', 'remaining_amount'] as $column) {
                    $query->orWhere($column, '<', 0);
                }
            })
            ->limit(5)
            ->pluck('id')
            ->all();

        if ($negative !== []) {
            throw new RuntimeException('Cannot harden purchase adjustment allocation ledger: negative allocation values exist. Sample IDs: '.implode(', ', $negative));
        }
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

            try {
                DB::statement("ALTER TABLE {$table} DROP CHECK {$name}");
            } catch (Throwable) {
                DB::statement("ALTER TABLE {$table} DROP CONSTRAINT {$name}");
            }
        }
    }

    /**
     * @return list<array{0: string, 1: string, 2: string}>
     */
    private function checks(): array
    {
        return [
            ['purchase_adjustment_allocations', 'purchase_adj_alloc_entry_type_chk', "entry_type IN ('allocation','reversal')"],
            ['purchase_adjustment_allocations', 'purchase_adj_alloc_stage_chk', "stage IN ('manual_plan','grn_recognition','invoice_recognition','return_recognition')"],
            ['purchase_adjustment_allocations', 'purchase_adj_alloc_method_chk', "allocation_method IN ('proportional','manual','first_invoice','last_invoice')"],
            ['purchase_adjustment_allocations', 'purchase_adj_alloc_amounts_chk', 'source_amount >= 0 AND allocated_amount >= 0 AND recognized_at_grn_amount >= 0 AND recognized_at_invoice_amount >= 0 AND remaining_amount >= 0'],
            ['purchase_adjustment_allocations', 'purchase_adj_alloc_reversal_chk', "(entry_type = 'reversal' AND reversal_of_id IS NOT NULL) OR (entry_type = 'allocation' AND reversal_of_id IS NULL)"],
        ];
    }

    private function dropIndexIfExists(Blueprint $table, string $name): void
    {
        try {
            $table->dropIndex($name);
        } catch (Throwable) {
            // Keep rollback resilient across partially-applied local migrations.
        }
    }
};
