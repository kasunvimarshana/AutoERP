<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pos_cash_register_transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->uuid('cash_register_id');
            $table->string('type'); // cash_in, cash_out
            $table->decimal('amount', 15, 2);
            $table->string('payment_method')->default('cash');
            $table->text('notes')->nullable();
            $table->uuid('transaction_id')->nullable(); // related POS transaction
            $table->uuid('created_by');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('cash_register_id')->references('id')->on('pos_cash_registers')->onDelete('cascade');
            $table->index(['tenant_id', 'cash_register_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pos_cash_register_transactions');
    }
};
