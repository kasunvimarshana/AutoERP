<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Cash Registers (POS terminals at a business location)
        Schema::create('cash_registers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('business_location_id')->constrained('business_locations')->cascadeOnDelete();
            $table->string('name');
            $table->decimal('closing_balance', 20, 8)->default(0);
            $table->string('status')->default('closed'); // open, closed
            $table->foreignUuid('opened_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('closed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('opened_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->json('denominations')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'business_location_id', 'status']);
        });

        // Cash Register Transactions (cash in/out entries)
        Schema::create('cash_register_transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('cash_register_id')->constrained('cash_registers')->cascadeOnDelete();
            $table->string('type'); // pay_in, pay_out, opening, closing
            $table->decimal('amount', 20, 8);
            $table->string('note')->nullable();
            $table->foreignUuid('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->index('cash_register_id');
        });

        // POS Transactions (sales at point of sale)
        Schema::create('pos_transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('business_location_id')->constrained('business_locations')->cascadeOnDelete();
            $table->foreignUuid('cash_register_id')->nullable()->constrained('cash_registers')->nullOnDelete();
            $table->string('reference_no')->nullable();
            $table->string('status')->default('completed'); // pending, completed, void, refunded
            $table->foreignUuid('customer_id')->nullable()->constrained('contacts')->nullOnDelete();
            $table->foreignUuid('customer_group_id')->nullable()->constrained('customer_groups')->nullOnDelete();
            $table->decimal('subtotal', 20, 8)->default(0);
            $table->decimal('discount_amount', 20, 8)->default(0);
            $table->decimal('tax_amount', 20, 8)->default(0);
            $table->decimal('total', 20, 8)->default(0);
            $table->decimal('paid_amount', 20, 8)->default(0);
            $table->decimal('change_amount', 20, 8)->default(0);
            $table->string('notes')->nullable();
            $table->foreignUuid('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'reference_no']);
            $table->index(['tenant_id', 'business_location_id', 'status']);
            $table->index(['tenant_id', 'created_at']);
        });

        // POS Transaction Lines (items sold)
        Schema::create('pos_transaction_lines', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('pos_transaction_id')->constrained('pos_transactions')->cascadeOnDelete();
            $table->foreignUuid('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignUuid('product_variant_id')->nullable()->constrained('product_variants')->nullOnDelete();
            $table->decimal('quantity', 20, 8);
            $table->decimal('unit_price', 20, 8);
            $table->decimal('discount_percent', 8, 4)->default(0);
            $table->decimal('discount_amount', 20, 8)->default(0);
            $table->decimal('tax_percent', 8, 4)->default(0);
            $table->decimal('tax_amount', 20, 8)->default(0);
            $table->decimal('line_total', 20, 8);
            $table->json('modifiers')->nullable(); // for restaurant-style modifiers
            $table->timestamps();

            $table->index('pos_transaction_id');
        });

        // POS Transaction Payments (how the POS sale was paid)
        Schema::create('pos_transaction_payments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('pos_transaction_id')->constrained('pos_transactions')->cascadeOnDelete();
            $table->foreignUuid('payment_account_id')->nullable()->constrained('payment_accounts')->nullOnDelete();
            $table->string('method'); // cash, card, mobile_money, credit, etc.
            $table->decimal('amount', 20, 8);
            $table->string('reference')->nullable(); // card auth code, transaction ref, etc.
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index('pos_transaction_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pos_transaction_payments');
        Schema::dropIfExists('pos_transaction_lines');
        Schema::dropIfExists('pos_transactions');
        Schema::dropIfExists('cash_register_transactions');
        Schema::dropIfExists('cash_registers');
    }
};
