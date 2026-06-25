<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoice_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->restrictOnDelete();
            $table->foreignId('organization_unit_id')->nullable();
            $table->foreignId('invoice_id')->unique('invoice_balances_invoice_uk');
            $table->decimal('invoice_total', 20, 6)->default('0');
            $table->decimal('paid_amount', 20, 6)->default('0');
            $table->decimal('credit_allocated_amount', 20, 6)->default('0');
            $table->decimal('debit_allocated_amount', 20, 6)->default('0');
            $table->decimal('refunded_amount', 20, 6)->default('0');
            $table->decimal('remaining_amount', 20, 6)->default('0');
            $table->enum('status', ['unpaid', 'partial', 'paid', 'overpaid', 'cancelled', 'void'])->default('unpaid');
            $table->timestamps();

            $table->unique(['id', 'tenant_id'], 'invoice_balances_id_tenant_uk');
            $table->foreign(['organization_unit_id', 'tenant_id'], 'invoice_balances_organization_unit_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('organization_units')
                ->restrictOnDelete();
            $table->foreign(['invoice_id', 'tenant_id'], 'invoice_balances_invoice_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('invoices')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_balances');
    }
};
