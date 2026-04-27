<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_returns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('org_unit_id')->nullable()->constrained('org_units', 'id')->nullOnDelete();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('customer_id');
            $table->foreignId('original_sales_order_id')->nullable()->constrained('sales_orders', 'id', 'sales_returns_original_sales_order_id_fk')->nullOnDelete();
            $table->foreignId('original_invoice_id')->nullable()->constrained('sales_invoices', 'id', 'sales_returns_original_invoice_id_fk')->nullOnDelete();
            $table->string('return_number');
            $table->enum('status', ['draft', 'approved', 'received', 'closed', 'cancelled'])->default('draft');
            $table->date('return_date');
            $table->string('return_reason')->nullable();
            $table->foreignId('currency_id')->constrained('currencies', 'id', 'sales_returns_currency_id_fk');
            $table->decimal('exchange_rate', 20, 10)->default(1);
            $table->decimal('subtotal', 20, 6)->default(0);
            $table->decimal('tax_total', 20, 6)->default(0);
            $table->decimal('restocking_fee_total', 20, 6)->default(0);
            $table->decimal('grand_total', 20, 6)->default(0);
            $table->string('credit_memo_number')->nullable();
            $table->foreignId('journal_entry_id')->nullable();
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();

            $table->foreign('customer_id')->references('id')->on('customers')->cascadeOnDelete();
            $table->foreign('journal_entry_id')->references('id')->on('journal_entries')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'return_number'], 'sales_returns_tenant_return_uk');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_returns');
    }
};
