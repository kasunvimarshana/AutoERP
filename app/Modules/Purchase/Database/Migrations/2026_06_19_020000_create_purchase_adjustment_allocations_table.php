<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_header_adjustments', function (Blueprint $table): void {
            $table->unsignedBigInteger('origin_purchase_header_adjustment_id')
                ->nullable()
                ->after('source_id');

            $table->foreign('origin_purchase_header_adjustment_id', 'purchase_header_adjustments_origin_fk')
                ->references('id')
                ->on('purchase_header_adjustments')
                ->nullOnDelete();
            $table->index('origin_purchase_header_adjustment_id', 'purchase_header_adjustments_origin_idx');
        });

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
            $table->index(['tenant_id', 'organization_unit_id'], 'purchase_adj_alloc_tenant_org_idx');
            $table->index(['purchase_header_adjustment_id', 'stage'], 'purchase_adj_alloc_source_stage_idx');
            $table->index('target_purchase_header_adjustment_id', 'purchase_adj_alloc_target_adj_idx');
            $table->index(['target_type', 'target_id'], 'purchase_adj_alloc_target_idx');
            $table->index(['target_line_type', 'target_line_id'], 'purchase_adj_alloc_target_line_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_adjustment_allocations');

        Schema::table('purchase_header_adjustments', function (Blueprint $table): void {
            $table->dropForeign('purchase_header_adjustments_origin_fk');
            $table->dropIndex('purchase_header_adjustments_origin_idx');
            $table->dropColumn('origin_purchase_header_adjustment_id');
        });
    }
};
