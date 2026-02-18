<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quotations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->string('quote_number', 50)->unique();
            $table->foreignId('customer_id')->constrained('customers')->onDelete('restrict');
            $table->date('quote_date');
            $table->date('valid_until')->nullable();
            $table->string('status', 50)->default('draft'); // draft, sent, accepted, rejected, expired, converted
            $table->string('currency', 10)->default('USD');
            $table->decimal('exchange_rate', 15, 6)->default(1.000000);
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->text('terms_and_conditions')->nullable();
            $table->text('notes')->nullable();
            $table->json('custom_fields')->nullable();
            $table->foreignId('converted_to_order_id')->nullable()->constrained('sales_orders')->onDelete('set null');
            $table->timestamp('converted_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'status']);
            $table->index(['customer_id', 'quote_date']);
            $table->index('quote_number');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quotations');
    }
};
