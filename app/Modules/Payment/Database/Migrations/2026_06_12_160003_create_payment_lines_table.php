<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units', 'id')->nullOnDelete();
            $table->foreignId('payment_id')->constrained('payments', 'id')->cascadeOnDelete();
            $table->foreignId('payment_method_id')->nullable()->constrained('payment_methods', 'id')->nullOnDelete();
            $table->foreignId('internal_bank_account_id')->nullable()->constrained('finance_accounts', 'id')->nullOnDelete();
            $table->string('reference_number')->nullable();
            $table->decimal('amount', 20, 6);
            $table->decimal('cleared_amount', 20, 6)->default('0');
            $table->string('status')->default('pending');
            $table->string('instrument_direction', 20)->nullable();
            $table->string('external_bank_name')->nullable();
            $table->string('external_bank_branch')->nullable();
            $table->string('instrument_number', 150)->nullable();
            $table->date('instrument_date')->nullable();
            $table->date('deposit_date')->nullable();
            $table->date('realized_date')->nullable();
            $table->date('clearing_date')->nullable();
            $table->date('bounced_date')->nullable();
            $table->date('returned_date')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index('payment_id', 'payment_lines_payment_idx');
            $table->index(['payment_method_id', 'status'], 'payment_lines_method_status_idx');
            $table->index(['instrument_number', 'external_bank_name'], 'payment_lines_instrument_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_lines');
    }
};
