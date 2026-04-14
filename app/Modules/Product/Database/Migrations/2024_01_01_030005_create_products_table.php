<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->foreignId('brand_id')->nullable()->constrained('brands')->nullOnDelete();
            $table->string('sku', 100)->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('type', ['physical', 'service', 'digital', 'combo', 'variable']);
            $table->foreignId('base_uom_id')->constrained('units_of_measure')->cascadeOnDelete();
            $table->foreignId('purchase_uom_id')->nullable()->constrained('units_of_measure')->nullOnDelete();
            $table->foreignId('sales_uom_id')->nullable()->constrained('units_of_measure')->nullOnDelete();
            $table->boolean('track_inventory')->default(true);
            $table->boolean('track_batch')->default(false);
            $table->boolean('track_serial')->default(false);
            $table->boolean('has_expiry')->default(false);
            $table->decimal('min_stock_level', 18, 4)->nullable();
            $table->decimal('reorder_point', 18, 4)->nullable();
            $table->enum('valuation_method', ['FIFO', 'LIFO', 'FEFO', 'WAC', 'SPECIFIC'])->default('FIFO');
            $table->foreignId('inventory_account_id')->nullable()->constrained('chart_of_accounts')->nullOnDelete();
            $table->foreignId('cogs_account_id')->nullable()->constrained('chart_of_accounts')->nullOnDelete();
            $table->foreignId('income_account_id')->nullable()->constrained('chart_of_accounts')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};