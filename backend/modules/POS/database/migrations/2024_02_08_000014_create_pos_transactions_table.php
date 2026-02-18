<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pos_transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->uuid('location_id');
            $table->string('type'); // sale, purchase, return, etc
            $table->string('status')->default('draft'); // draft, pending, completed, cancelled
            $table->string('transaction_number');
            $table->uuid('contact_id')->nullable(); // customer or supplier
            $table->uuid('cash_register_id')->nullable();
            $table->timestamp('transaction_date');
            $table->string('invoice_scheme_id')->nullable();
            $table->string('invoice_number')->nullable();
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->string('discount_type')->nullable(); // percentage, fixed
            $table->decimal('shipping_charges', 15, 2)->default(0);
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->string('payment_status')->default('due'); // paid, partial, due
            $table->decimal('paid_amount', 15, 2)->default(0);
            $table->text('notes')->nullable();
            $table->json('additional_data')->nullable();
            $table->uuid('created_by');
            $table->uuid('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('location_id')->references('id')->on('pos_business_locations')->onDelete('restrict');
            $table->foreign('cash_register_id')->references('id')->on('pos_cash_registers')->onDelete('set null');
            $table->unique(['tenant_id', 'transaction_number']);
            $table->index(['tenant_id', 'location_id', 'type', 'status']);
            $table->index(['transaction_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pos_transactions');
    }
};
