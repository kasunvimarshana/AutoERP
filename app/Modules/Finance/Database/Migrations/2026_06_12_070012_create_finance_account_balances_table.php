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
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units', 'id')->nullOnDelete();
            $table->foreignId('account_id')->constrained('finance_accounts', 'id')->cascadeOnDelete();
            $table->foreignId('fiscal_year_id')->nullable()->constrained('finance_fiscal_years', 'id')->nullOnDelete();
            $table->foreignId('fiscal_period_id')->nullable()->constrained('finance_fiscal_periods', 'id')->nullOnDelete();
            $table->decimal('opening_debit', 20, 6)->default('0');
            $table->decimal('opening_credit', 20, 6)->default('0');
            $table->decimal('period_debit', 20, 6)->default('0');
            $table->decimal('period_credit', 20, 6)->default('0');
            $table->decimal('closing_debit', 20, 6)->default('0');
            $table->decimal('closing_credit', 20, 6)->default('0');
            $table->timestamps();

            $table->unique(
                ['tenant_id', 'organization_unit_id', 'account_id', 'fiscal_period_id'],
                'finance_account_balances_period_uk'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_account_balances');
    }
};
