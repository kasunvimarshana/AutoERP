<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Sales Orders table
        Schema::create('sales_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->string('order_number')->unique();
            $table->foreignId('customer_id')->constrained()->onDelete('restrict');
            $table->foreignId('warehouse_id')->constrained()->onDelete('restrict');
            $table->foreignId('user_id')->comment('Sales person')->constrained()->onDelete('restrict');
            $table->string('status')->default('draft');
            $table->date('order_date');
            $table->date('delivery_date')->nullable();
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->text('notes')->nullable();
            $table->text('customer_notes')->nullable();
            $table->text('terms_and_conditions')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'customer_id']);
            $table->index(['tenant_id', 'status']);
            $table->index('order_date');
        });

        // Sales Order Items table
        Schema::create('sales_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_order_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->constrained()->onDelete('restrict');
            $table->integer('line_number');
            $table->decimal('quantity', 15, 3);
            $table->string('unit');
            $table->decimal('unit_price', 15, 2);
            $table->decimal('discount_percentage', 5, 2)->default(0);
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('tax_percentage', 5, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('line_total', 15, 2);
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index('sales_order_id');
            $table->index('product_id');
        });

        // Invoices table
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->string('invoice_number')->unique();
            $table->foreignId('sales_order_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('customer_id')->constrained()->onDelete('restrict');
            $table->string('status')->default('pending');
            $table->date('invoice_date');
            $table->date('due_date');
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->decimal('paid_amount', 15, 2)->default(0);
            $table->decimal('balance_amount', 15, 2)->default(0);
            $table->text('notes')->nullable();
            $table->text('terms_and_conditions')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'customer_id']);
            $table->index(['tenant_id', 'status']);
            $table->index('invoice_date');
            $table->index('due_date');
        });

        // Invoice Items table
        Schema::create('invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->constrained()->onDelete('restrict');
            $table->integer('line_number');
            $table->decimal('quantity', 15, 3);
            $table->string('unit');
            $table->decimal('unit_price', 15, 2);
            $table->decimal('discount_percentage', 5, 2)->default(0);
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('tax_percentage', 5, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('line_total', 15, 2);
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index('invoice_id');
            $table->index('product_id');
        });

        // Payments table
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->string('payment_number')->unique();
            $table->foreignId('invoice_id')->constrained()->onDelete('restrict');
            $table->foreignId('customer_id')->constrained()->onDelete('restrict');
            $table->string('payment_method');
            $table->decimal('amount', 15, 2);
            $table->date('payment_date');
            $table->string('reference_number')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'customer_id']);
            $table->index(['tenant_id', 'invoice_id']);
            $table->index('payment_date');
        });

        // Quotations table (for sales quotes before orders)
        Schema::create('quotations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->string('quotation_number')->unique();
            $table->foreignId('customer_id')->constrained()->onDelete('restrict');
            $table->foreignId('user_id')->comment('Sales person')->constrained()->onDelete('restrict');
            $table->string('status')->default('draft');
            $table->date('quotation_date');
            $table->date('valid_until');
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->text('notes')->nullable();
            $table->text('terms_and_conditions')->nullable();
            $table->foreignId('converted_to_sales_order_id')->nullable()->constrained('sales_orders')->onDelete('set null');
            $table->timestamp('converted_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'customer_id']);
            $table->index(['tenant_id', 'status']);
            $table->index('quotation_date');
        });

        // Quotation Items table
        Schema::create('quotation_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quotation_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->constrained()->onDelete('restrict');
            $table->integer('line_number');
            $table->decimal('quantity', 15, 3);
            $table->string('unit');
            $table->decimal('unit_price', 15, 2);
            $table->decimal('discount_percentage', 5, 2)->default(0);
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('tax_percentage', 5, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('line_total', 15, 2);
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index('quotation_id');
            $table->index('product_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quotation_items');
        Schema::dropIfExists('quotations');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('invoice_items');
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('sales_order_items');
        Schema::dropIfExists('sales_orders');
    }
};
