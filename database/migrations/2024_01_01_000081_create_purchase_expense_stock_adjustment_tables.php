<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Purchases (procurement from suppliers)
        Schema::create('purchases', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('business_location_id')->nullable()->constrained('business_locations')->nullOnDelete();
            $table->string('reference_no')->nullable();
            $table->string('status')->default('ordered'); // ordered, received, partial, cancelled
            $table->foreignUuid('supplier_id')->nullable()->constrained('contacts')->nullOnDelete();
            $table->date('purchase_date');
            $table->date('expected_delivery_date')->nullable();
            $table->decimal('subtotal', 20, 8)->default(0);
            $table->decimal('discount_amount', 20, 8)->default(0);
            $table->decimal('tax_amount', 20, 8)->default(0);
            $table->decimal('shipping_amount', 20, 8)->default(0);
            $table->decimal('total', 20, 8)->default(0);
            $table->decimal('paid_amount', 20, 8)->default(0);
            $table->string('payment_status')->default('pending'); // pending, partial, paid
            $table->text('notes')->nullable();
            $table->foreignUuid('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'reference_no']);
            $table->index(['tenant_id', 'status', 'payment_status']);
            $table->index(['tenant_id', 'supplier_id']);
        });

        // Purchase Lines (items in a purchase order)
        Schema::create('purchase_lines', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('purchase_id')->constrained('purchases')->cascadeOnDelete();
            $table->foreignUuid('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignUuid('product_variant_id')->nullable()->constrained('product_variants')->nullOnDelete();
            $table->decimal('quantity_ordered', 20, 8);
            $table->decimal('quantity_received', 20, 8)->default(0);
            $table->decimal('unit_cost', 20, 8);
            $table->decimal('discount_percent', 8, 4)->default(0);
            $table->decimal('discount_amount', 20, 8)->default(0);
            $table->decimal('tax_percent', 8, 4)->default(0);
            $table->decimal('tax_amount', 20, 8)->default(0);
            $table->decimal('line_total', 20, 8);
            $table->timestamps();

            $table->index('purchase_id');
        });

        // Expense Categories
        Schema::create('expense_categories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('code')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'name']);
        });

        // Expenses
        Schema::create('expenses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('business_location_id')->nullable()->constrained('business_locations')->nullOnDelete();
            $table->foreignUuid('expense_category_id')->constrained('expense_categories')->cascadeOnDelete();
            $table->foreignUuid('payment_account_id')->nullable()->constrained('payment_accounts')->nullOnDelete();
            $table->string('reference_no')->nullable();
            $table->decimal('amount', 20, 8);
            $table->string('note')->nullable();
            $table->date('expense_date');
            $table->foreignUuid('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'expense_category_id']);
            $table->index(['tenant_id', 'expense_date']);
        });

        // Stock Adjustments (manual corrections)
        Schema::create('stock_adjustments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('warehouse_id')->constrained('warehouses')->cascadeOnDelete();
            $table->string('reference_no')->nullable();
            $table->string('reason'); // damage, theft, expiry, correction, audit, other
            $table->text('notes')->nullable();
            $table->decimal('total_amount', 20, 8)->default(0); // monetary impact
            $table->foreignUuid('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'reference_no']);
            $table->index(['tenant_id', 'warehouse_id']);
        });

        // Stock Adjustment Lines
        Schema::create('stock_adjustment_lines', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('stock_adjustment_id')->constrained('stock_adjustments')->cascadeOnDelete();
            $table->foreignUuid('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignUuid('product_variant_id')->nullable()->constrained('product_variants')->nullOnDelete();
            $table->decimal('quantity', 20, 8); // positive = add, negative = remove
            $table->decimal('unit_cost', 20, 8)->default(0);
            $table->timestamps();

            $table->index('stock_adjustment_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_adjustment_lines');
        Schema::dropIfExists('stock_adjustments');
        Schema::dropIfExists('expenses');
        Schema::dropIfExists('expense_categories');
        Schema::dropIfExists('purchase_lines');
        Schema::dropIfExists('purchases');
    }
};
