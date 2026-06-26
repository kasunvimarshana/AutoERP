<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_header_adjustments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants', indexName: 'sales_header_adjustments_tenant_fk')->restrictOnDelete();
            $table->foreignId('organization_unit_id')->nullable();
            $table->string('source_type');
            $table->unsignedBigInteger('source_id');
            $table->string('name');
            $table->string('adjustment_type');
            $table->string('effect');
            $table->string('calculation_type');
            $table->string('calculation_base');
            $table->decimal('rate', 20, 6)->default('0.000000');
            $table->decimal('amount', 20, 6);
            $table->decimal('allocated_amount', 20, 6)->default('0.000000');
            $table->decimal('returned_amount', 20, 6)->default('0.000000');
            $table->decimal('remaining_amount', 20, 6);
            $table->string('allocation_method')->default('proportional');
            $table->boolean('is_allocatable')->default(true);
            $table->integer('sort_order')->default(0);
            $table->text('description')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'organization_unit_id'], 'sales_header_adjustments_scope_ix');
            $table->index(['source_type', 'source_id'], 'sales_header_adjustments_source_ix');

            $table->unique(['id', 'tenant_id'], 'sales_header_adjustments_id_tenant_uk');
            $table->foreign(['organization_unit_id', 'tenant_id'], 'sales_header_adjustments_organization_unit_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('organization_units')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_header_adjustments');
    }
};
