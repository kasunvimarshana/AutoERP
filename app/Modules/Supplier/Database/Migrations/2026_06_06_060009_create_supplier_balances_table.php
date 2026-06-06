<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supplier_balances', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units')->nullOnDelete();
            $table->foreignId('supplier_id')->unique()->constrained('suppliers')->cascadeOnDelete();
            $table->decimal('opening_balance', 20, 6)->default('0.000000');
            $table->decimal('invoice_total', 20, 6)->default('0.000000');
            $table->decimal('payment_total', 20, 6)->default('0.000000');
            $table->decimal('credit_total', 20, 6)->default('0.000000');
            $table->decimal('debit_total', 20, 6)->default('0.000000');
            $table->decimal('outstanding_balance', 20, 6)->default('0.000000');
            $table->date('last_transaction_date')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'organization_unit_id'], 'supplier_balances_tenant_org_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_balances');
    }
};
