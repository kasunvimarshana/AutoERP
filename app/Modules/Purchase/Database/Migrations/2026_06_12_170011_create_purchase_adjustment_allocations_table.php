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
        Schema::create('purchase_adjustment_allocations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units')->nullOnDelete();
            $table->unsignedBigInteger('purchase_header_adjustment_id');
            $table->unsignedBigInteger('target_purchase_header_adjustment_id')->nullable();
            $table->string('stage', 80);
            $table->string('source_type', 120)->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->string('target_type', 120)->nullable();
            $table->unsignedBigInteger('target_id')->nullable();
            $table->string('target_line_type', 120)->nullable();
            $table->unsignedBigInteger('target_line_id')->nullable();
            $table->string('allocation_method', 80);
            $table->string('calculation_base', 80)->nullable();
            $table->decimal('basis_amount', 20, 6)->default('0.000000');
            $table->decimal('source_amount', 20, 6);
            $table->decimal('signed_amount', 20, 6);
            $table->decimal('allocated_amount', 20, 6);
            $table->decimal('recognized_at_grn_amount', 20, 6)->default('0.000000');
            $table->decimal('recognized_at_invoice_amount', 20, 6)->default('0.000000');
            $table->decimal('remaining_amount', 20, 6)->default('0.000000');
            $table->string('cost_treatment', 80)->nullable();
            $table->string('tax_treatment', 80)->nullable();
            $table->unsignedBigInteger('finance_posting_profile_id')->nullable();
            $table->unsignedBigInteger('finance_account_id')->nullable();
            $table->string('entry_type', 30)->default('allocation');
            $table->unsignedBigInteger('reversal_of_id')->nullable();
            $table->string('correlation_key', 160)->nullable();
            $table->string('event_type', 80)->nullable();
            $table->json('provenance')->nullable();
            $table->timestamps();

            $table->foreign('purchase_header_adjustment_id', 'purchase_adj_alloc_source_adj_fk')
                ->references('id')
                ->on('purchase_header_adjustments')
                ->cascadeOnDelete();
            $table->foreign('target_purchase_header_adjustment_id', 'purchase_adj_alloc_target_adj_fk')
                ->references('id')
                ->on('purchase_header_adjustments')
                ->nullOnDelete();
            $table->foreign('finance_posting_profile_id', 'purchase_adj_alloc_fin_profile_fk')
                ->references('id')
                ->on('finance_posting_profiles')
                ->nullOnDelete();
            $table->foreign('finance_account_id', 'purchase_adj_alloc_fin_account_fk')
                ->references('id')
                ->on('finance_accounts')
                ->nullOnDelete();
            $table->foreign('reversal_of_id', 'purchase_adj_alloc_reversal_fk')
                ->references('id')
                ->on('purchase_adjustment_allocations')
                ->restrictOnDelete();
            $table->index(['tenant_id', 'organization_unit_id'], 'purchase_adj_alloc_tenant_org_idx');
            $table->index(['purchase_header_adjustment_id', 'stage'], 'purchase_adj_alloc_source_stage_idx');
            $table->index('target_purchase_header_adjustment_id', 'purchase_adj_alloc_target_adj_idx');
            $table->index(['target_type', 'target_id'], 'purchase_adj_alloc_target_idx');
            $table->index(['target_line_type', 'target_line_id'], 'purchase_adj_alloc_target_line_idx');
            $table->unique('correlation_key', 'purchase_adj_alloc_correlation_uk');
            $table->unique(['reversal_of_id', 'entry_type'], 'purchase_adj_alloc_one_reversal_uk');
            $table->index(['purchase_header_adjustment_id', 'entry_type', 'stage'], 'purchase_adj_alloc_effective_stage_idx');
            $table->index(['target_type', 'target_id', 'entry_type'], 'purchase_adj_alloc_target_effective_idx');
            $table->index(['event_type', 'entry_type'], 'purchase_adj_alloc_event_idx');
        });

        if (in_array(DB::getDriverName(), ['mysql', 'pgsql'], true)) {
            DB::statement("ALTER TABLE purchase_adjustment_allocations ADD CONSTRAINT purchase_adj_alloc_entry_type_chk CHECK (entry_type IN ('allocation','reversal'))");
            DB::statement("ALTER TABLE purchase_adjustment_allocations ADD CONSTRAINT purchase_adj_alloc_stage_chk CHECK (stage IN ('manual_plan','grn_recognition','invoice_recognition','return_recognition'))");
            DB::statement("ALTER TABLE purchase_adjustment_allocations ADD CONSTRAINT purchase_adj_alloc_method_chk CHECK (allocation_method IN ('proportional','manual','first_invoice','last_invoice'))");
            DB::statement('ALTER TABLE purchase_adjustment_allocations ADD CONSTRAINT purchase_adj_alloc_amounts_chk CHECK (source_amount >= 0 AND allocated_amount >= 0 AND recognized_at_grn_amount >= 0 AND recognized_at_invoice_amount >= 0 AND remaining_amount >= 0)');
            DB::statement("ALTER TABLE purchase_adjustment_allocations ADD CONSTRAINT purchase_adj_alloc_reversal_chk CHECK ((entry_type = 'reversal' AND reversal_of_id IS NOT NULL) OR (entry_type = 'allocation' AND reversal_of_id IS NULL))");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_adjustment_allocations');
    }
};
