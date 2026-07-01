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
            $table->foreignId('tenant_id')->constrained('tenants', indexName: 'purchase_header_adjustments_tenant_fk')->restrictOnDelete();
            $table->foreignId('organization_unit_id')->nullable();
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
            $table->string('cost_treatment', 80)->nullable();
            $table->string('tax_treatment', 80)->nullable();
            $table->string('recognition_source', 80)->default('catalogue');
            $table->integer('sort_order')->default(0);
            $table->text('description')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'organization_unit_id'], 'purchase_header_adjustments_tenant_org_ix');
            $table->index(['source_type', 'source_id'], 'purchase_header_adjustments_source_ix');
            $table->index('adjustment_type', 'purchase_header_adjustments_type_ix');
            $table->index('origin_purchase_header_adjustment_id', 'purchase_header_adjustments_origin_ix');

            $table->unique(['id', 'tenant_id'], 'purchase_header_adjustments_id_tenant_uk');
            $table->foreign(['organization_unit_id', 'tenant_id'], 'purchase_header_adjustments_organization_unit_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('organization_units')
                ->restrictOnDelete();
            $table->foreign(['origin_purchase_header_adjustment_id', 'tenant_id'], 'purchase_header_adjustments_origin_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('purchase_header_adjustments')
                ->restrictOnDelete();
            $table->foreign(['created_by', 'tenant_id'], 'purchase_header_adjustments_created_by_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('users')
                ->restrictOnDelete();
            $table->foreign(['updated_by', 'tenant_id'], 'purchase_header_adjustments_updated_by_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('users')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_header_adjustments');
    }
};
