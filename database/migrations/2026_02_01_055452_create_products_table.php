<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->uuid('uuid')->unique();
            $table->string('sku');
            $table->string('name');
            $table->text('description')->nullable();
            $table->foreignId('category_id')->nullable()->constrained('product_categories')->nullOnDelete();
            $table->foreignId('brand_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('unit_price', 15, 2)->default(0);
            $table->decimal('cost_price', 15, 2)->default(0);
            $table->string('unit_of_measure', 50)->default('piece');
            $table->boolean('track_inventory')->default(true);
            $table->boolean('track_batch')->default(false);
            $table->boolean('track_serial')->default(false);
            $table->boolean('track_expiry')->default(false);
            $table->decimal('min_stock_level', 15, 2)->default(0);
            $table->decimal('max_stock_level', 15, 2)->default(0);
            $table->decimal('reorder_point', 15, 2)->default(0);
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
            $table->softDeletes();
            
            $table->unique(['tenant_id', 'sku']);
            $table->index(['tenant_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
