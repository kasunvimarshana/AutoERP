<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
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

    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_adjustment_allocations');
    }
};
