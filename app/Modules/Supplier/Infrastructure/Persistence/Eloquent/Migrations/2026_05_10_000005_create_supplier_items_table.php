<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supplier_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete()->comment('Multi-tenant owner reference');
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units', 'id')->nullOnDelete()->comment('Branch or department ownership');
            $table->json('metadata')->nullable()->comment('Extensible custom dynamic data');

            $table->foreignId('supplier_id')->constrained('suppliers', 'id')->cascadeOnDelete();
            $table->foreignId('item_id')->constrained('items', 'id')->cascadeOnDelete();
            $table->foreignId('variant_id')->nullable()->constrained('item_variants', 'id')->nullOnDelete();
            $table->string('supplier_sku')->nullable();
            $table->unsignedInteger('lead_time_days')->nullable();
            $table->decimal('min_order_qty', 20, 4)->default(1);
            $table->boolean('is_preferred')->default(false);
            $table->decimal('last_purchase_price', 20, 4)->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'supplier_id', 'item_id', 'variant_id'], 'supplier_items_supplier_item_variant_uk');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_items');
    }
};
