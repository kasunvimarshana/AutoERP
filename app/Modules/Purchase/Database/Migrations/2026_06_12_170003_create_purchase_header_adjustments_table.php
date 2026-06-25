<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_header_adjustments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units')->nullOnDelete();
            $table->string('source_type');
            $table->unsignedBigInteger('source_id');
            $table->unsignedBigInteger('origin_purchase_header_adjustment_id')->nullable();
            $table->string('name');
            $table->string('adjustment_type');
            $table->string('effect');
            $table->string('calculation_type')->default('fixed');
            $table->string('calculation_base')->default('subtotal');
            $table->decimal('rate', 20, 6)->default('0.000000');
            $table->decimal('amount', 20, 6);
            $table->decimal('allocated_amount', 20, 6)->default('0.000000');
            $table->decimal('returned_amount', 20, 6)->default('0.000000');
            $table->decimal('remaining_amount', 20, 6);
            $table->string('allocation_method')->default('proportional');
            $table->boolean('is_allocatable')->default(true);
            $table->foreignId('finance_posting_profile_id')->nullable()->constrained('finance_posting_profiles', 'id')->nullOnDelete();
            $table->foreignId('finance_account_id')->nullable()->constrained('finance_accounts', 'id')->nullOnDelete();
            $table->string('cost_treatment', 80)->nullable();
            $table->string('tax_treatment', 80)->nullable();
            $table->string('mapping_source', 80)->default('catalogue');
            $table->text('override_reason')->nullable();
            $table->integer('sort_order')->default(0);
            $table->text('description')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'organization_unit_id'], 'purchase_header_adjustments_tenant_org_idx');
            $table->index(['source_type', 'source_id'], 'purchase_header_adjustments_source_idx');
            $table->index('adjustment_type', 'purchase_header_adjustments_type_idx');
            $table->index('finance_posting_profile_id', 'purchase_header_adjustments_profile_idx');
            $table->index('finance_account_id', 'purchase_header_adjustments_account_idx');
            $table->foreign('origin_purchase_header_adjustment_id', 'purchase_header_adjustments_origin_fk')
                ->references('id')
                ->on('purchase_header_adjustments')
                ->nullOnDelete();
            $table->index('origin_purchase_header_adjustment_id', 'purchase_header_adjustments_origin_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_header_adjustments');
    }
};
