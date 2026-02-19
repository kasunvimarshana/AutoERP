<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('warehouses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('organization_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('code')->nullable();
            $table->json('address')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'code']);
            $table->index(['tenant_id', 'is_active']);
        });

        Schema::create('stock_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('warehouse_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('product_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('variant_id')->nullable()->constrained('product_variants')->nullOnDelete();
            $table->decimal('quantity_on_hand', 20, 8)->default(0);
            $table->decimal('quantity_reserved', 20, 8)->default(0);
            $table->decimal('quantity_available', 20, 8)->default(0);
            $table->decimal('reorder_point', 20, 8)->default(0);
            $table->decimal('reorder_quantity', 20, 8)->default(0);
            $table->decimal('cost_per_unit', 20, 8)->default(0);
            $table->string('currency', 3)->default('USD');
            $table->unsignedInteger('lock_version')->default(0);
            $table->timestamps();

            $table->unique(['warehouse_id', 'product_id', 'variant_id']);
            $table->index(['tenant_id', 'product_id']);
        });

        Schema::create('stock_movements', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('warehouse_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('product_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('variant_id')->nullable()->constrained('product_variants')->nullOnDelete();
            $table->string('movement_type'); // receipt, shipment, adjustment, transfer, return
            $table->decimal('quantity', 20, 8);
            $table->decimal('cost_per_unit', 20, 8)->default(0);
            $table->string('reference_type')->nullable(); // order, purchase_order, etc.
            $table->string('reference_id')->nullable();
            $table->text('notes')->nullable();
            $table->foreignUuid('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('moved_at')->useCurrent();
            $table->timestamps();

            $table->index(['tenant_id', 'product_id', 'moved_at']);
            $table->index(['reference_type', 'reference_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
        Schema::dropIfExists('stock_items');
        Schema::dropIfExists('warehouses');
    }
};
