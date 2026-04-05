<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('pos_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('session_id')->constrained('pos_sessions')->cascadeOnDelete();
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->string('type', 20)->default('sale');      // sale|refund
            $table->string('status', 20)->default('pending'); // pending|completed|voided
            $table->string('currency', 3)->default('USD');
            $table->decimal('subtotal', 15, 4)->default(0);
            $table->decimal('tax_total', 15, 4)->default(0);
            $table->decimal('discount_total', 15, 4)->default(0);
            $table->decimal('total', 15, 4)->default(0);
            $table->string('payment_method', 30)->default('cash'); // cash|card|split|voucher|credit
            $table->decimal('amount_tendered', 15, 4)->nullable();
            $table->decimal('change_given', 15, 4)->nullable();
            $table->string('reference', 100)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['tenant_id', 'session_id', 'status']);
            $table->index(['tenant_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pos_transactions');
    }
};
