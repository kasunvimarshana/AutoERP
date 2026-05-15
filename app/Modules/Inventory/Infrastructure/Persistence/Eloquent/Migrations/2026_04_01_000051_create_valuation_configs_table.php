<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('valuation_configs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units', 'id')->nullOnDelete();
            $table->json('metadata')->nullable();

            $table->unsignedBigInteger('warehouse_id')->nullable();
            $table->foreign('warehouse_id')->references('id')->on('warehouses')->nullOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->string('transaction_type', 50)->nullable();
            $table->string('valuation_method')->nullable();
            $table->string('allocation_strategy')->default('fifo');
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->unique(
                ['tenant_id', 'organization_unit_id', 'product_id', 'warehouse_id', 'transaction_type'],
                'valuation_configs_product_warehouse_transaction_uk'
            );
            $table->index(['tenant_id', 'organization_unit_id', 'is_active'], 'valuation_configs_active_idx');
            $table->index(['tenant_id', 'organization_unit_id', 'product_id'], 'valuation_configs_product_fk');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('valuation_configs');
    }
};
