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
        Schema::create('discount_tiers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('products')->cascadeOnDelete();
            $table->foreignId('pricing_rule_id')->nullable()->constrained('pricing_rules')->cascadeOnDelete();
            $table->enum('tier_type', ['buying', 'selling'])->default('selling');
            
            // Quantity thresholds
            $table->decimal('min_quantity', 15, 4)->default(0);
            $table->decimal('max_quantity', 15, 4)->nullable();
            
            // Discount configuration
            $table->enum('discount_type', ['flat', 'percentage'])->default('percentage');
            $table->decimal('discount_value', 10, 2)->default(0);
            
            // Alternative: fixed price for this tier
            $table->decimal('fixed_price', 15, 2)->nullable();
            
            // Display
            $table->string('label')->nullable(); // e.g., "Bulk Discount", "Wholesale"
            $table->integer('display_order')->default(0);
            
            $table->timestamps();
            
            $table->index(['tenant_id', 'product_id']);
            $table->index(['tenant_id', 'pricing_rule_id']);
            $table->index(['tier_type', 'min_quantity']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('discount_tiers');
    }
};
