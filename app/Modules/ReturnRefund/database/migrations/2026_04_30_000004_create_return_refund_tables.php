<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('return_inspections', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->uuid('rental_transaction_id');
            $table->boolean('is_damaged')->default(false);
            $table->text('damage_notes')->nullable();
            $table->decimal('damage_charge', 20, 6)->default(0);
            $table->decimal('fuel_adjustment_charge', 20, 6)->default(0);
            $table->decimal('late_return_charge', 20, 6)->default(0);
            $table->dateTime('inspected_at');
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->index(['tenant_id', 'rental_transaction_id']);
            $table->unique(['tenant_id', 'rental_transaction_id']);
        });

        Schema::create('refund_transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->uuid('rental_transaction_id');
            $table->string('refund_number')->unique();
            $table->decimal('gross_amount', 20, 6);
            $table->decimal('adjustment_amount', 20, 6)->default(0);
            $table->decimal('net_refund_amount', 20, 6);
            $table->enum('status', ['draft', 'processed', 'failed'])->default('draft');
            $table->uuid('finance_reference_id')->nullable();
            $table->dateTime('processed_at')->nullable();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'rental_transaction_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('refund_transactions');
        Schema::dropIfExists('return_inspections');
    }
};
