<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete()->comment('Multi-tenant owner reference');
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units', 'id')->nullOnDelete()->comment('Branch or department ownership');

            $table->string('item_code', 60);
            $table->string('name', 180);
            $table->string('display_name', 180)->nullable();
            $table->string('item_type', 60)->nullable()->comment('inventory, service, non_inventory');
            $table->foreignId('base_uom_id')->constrained('unit_of_measures')->restrictOnDelete();
            $table->foreignId('purchase_uom_id')->nullable()->constrained('unit_of_measures')->restrictOnDelete();
            $table->foreignId('sales_uom_id')->nullable()->constrained('unit_of_measures')->restrictOnDelete();
            $table->string('sku')->nullable();
            $table->string('barcode')->nullable();
            $table->text('description')->nullable();
            $table->boolean('track_inventory')->default(true);
            $table->boolean('is_stock_item')->default(true);
            $table->boolean('is_service_item')->default(false);
            $table->decimal('cost_price', 20, 4)->default(0);
            $table->decimal('sales_price', 20, 4)->default(0);
            $table->decimal('reorder_level', 20, 4)->default(0);
            $table->decimal('reorder_quantity', 20, 4)->default(0);
            $table->string('status', 60)->default('active')->comment('active, inactive');
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'item_code'], 'items_item_code_uk');
            $table->unique(['tenant_id', 'barcode'], 'items_barcode_uk');
            $table->index(['tenant_id', 'organization_unit_id'], 'items_organization_unit_idx');
            $table->index(['tenant_id', 'status'], 'items_status_idx');
            $table->index(['tenant_id', 'name'], 'items_name_idx');
            $table->index(['tenant_id', 'sku'], 'items_sku_idx');
            $table->index(['tenant_id', 'base_uom_id'], 'items_base_uom_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('items');
    }
};
