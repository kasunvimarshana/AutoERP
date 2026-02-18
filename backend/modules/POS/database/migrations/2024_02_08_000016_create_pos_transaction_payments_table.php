<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pos_transaction_payments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->uuid('transaction_id');
            $table->uuid('payment_method_id');
            $table->decimal('amount', 15, 2);
            $table->timestamp('payment_date');
            $table->string('payment_reference')->nullable();
            $table->text('notes')->nullable();
            $table->uuid('created_by');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('transaction_id')->references('id')->on('pos_transactions')->onDelete('cascade');
            $table->foreign('payment_method_id')->references('id')->on('pos_payment_methods')->onDelete('restrict');
            $table->index(['tenant_id', 'transaction_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pos_transaction_payments');
    }
};
