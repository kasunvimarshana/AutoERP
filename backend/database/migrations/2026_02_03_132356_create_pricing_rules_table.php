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
        Schema::create('pricing_rules', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('name');
            $table->string('code')->unique();
            $table->text('description')->nullable();
            $table->enum('rule_type', ['product', 'category', 'customer', 'customer_group', 'seasonal', 'promotional'])->default('product');
            $table->boolean('is_active')->default(true);
            
            // Applicability
            $table->foreignId('product_id')->nullable()->constrained('products')->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('product_categories')->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->cascadeOnDelete();
            $table->string('customer_group')->nullable();
            
            // Priority (higher number = higher priority)
            $table->integer('priority')->default(0);
            
            // Date range
            $table->date('valid_from')->nullable();
            $table->date('valid_to')->nullable();
            
            // Time-based (for seasonal pricing)
            $table->time('time_from')->nullable();
            $table->time('time_to')->nullable();
            $table->json('days_of_week')->nullable(); // [1,2,3,4,5] for Mon-Fri
            
            // Quantity-based
            $table->decimal('min_quantity', 15, 4)->default(0);
            $table->decimal('max_quantity', 15, 4)->nullable();
            
            // Pricing adjustments
            $table->enum('pricing_method', ['fixed', 'markup', 'markdown', 'discount'])->default('discount');
            $table->enum('adjustment_type', ['flat', 'percentage'])->default('percentage');
            $table->decimal('adjustment_value', 10, 2)->default(0);
            
            // Fixed price (if pricing_method is 'fixed')
            $table->decimal('fixed_price', 15, 2)->nullable();
            
            // Compound rules
            $table->boolean('can_compound')->default(false); // Can combine with other rules
            $table->json('exclude_rules')->nullable(); // IDs of rules to exclude
            
            // Conditions (JSON for complex conditions)
            $table->json('conditions')->nullable();
            
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['tenant_id', 'is_active']);
            $table->index(['tenant_id', 'rule_type']);
            $table->index(['tenant_id', 'product_id']);
            $table->index(['tenant_id', 'valid_from', 'valid_to']);
            $table->index('priority');
            $table->unique(['tenant_id', 'code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pricing_rules');
    }
};
