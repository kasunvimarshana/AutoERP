<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void
    {
        Schema::create('inventory_products', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->index();
            $table->string('name');
            $table->string('type')->default('physical');
            $table->string('sku');
            $table->uuid('category_id')->nullable()->index();
            $table->decimal('unit_price', 18, 8)->default(0);
            $table->decimal('cost_price', 18, 8)->default(0);
            $table->string('purchase_uom')->default('unit');
            $table->string('sale_uom')->default('unit');
            $table->string('inventory_uom')->default('unit');
            $table->string('status')->default('active');
            $table->string('barcode_ean13')->nullable();
            $table->boolean('track_lots')->default(false);
            $table->boolean('track_serials')->default(false);
            $table->text('description')->nullable();
            $table->text('internal_notes')->nullable();
            $table->decimal('reorder_point', 18, 8)->nullable();
            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['tenant_id', 'sku']);
        });
    }
    public function down(): void { Schema::dropIfExists('inventory_products'); }
};
