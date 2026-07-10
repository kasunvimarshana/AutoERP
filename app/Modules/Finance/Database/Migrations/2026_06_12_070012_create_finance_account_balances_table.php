<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finance_account_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants', 'id', indexName: 'finance_account_balances_tenant_fk')->restrictOnDelete();
            $table->foreignId('organization_unit_id')->nullable();
            $table->foreignId('account_id');
            $table->decimal('total_debit', 20, 6)->default('0');
            $table->decimal('total_credit', 20, 6)->default('0');
            $table->decimal('closing_debit', 20, 6)->default('0');
            $table->decimal('closing_credit', 20, 6)->default('0');
            $table->timestamps();

            $table->unique(
                ['tenant_id', 'account_id'],
                'finance_account_balances_account_uk'
            );

            $table->unique(['id', 'tenant_id'], 'finance_account_balances_id_tenant_uk');
            $table->foreign(['organization_unit_id', 'tenant_id'], 'finance_account_balances_organization_unit_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('organization_units')
                ->restrictOnDelete();
            $table->foreign(['account_id', 'tenant_id'], 'finance_account_balances_account_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('finance_accounts')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_account_balances');
    }
};
