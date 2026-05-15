<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('period_end_balances', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units', 'id')->nullOnDelete();
            $table->json('metadata')->nullable();

            $table->foreignId('account_id')->constrained('chart_of_accounts');
            $table->foreignId('fiscal_period_id')->constrained('fiscal_periods');
            $table->decimal('debit_balance', 20, 4)->default(0);
            $table->decimal('credit_balance', 20, 4)->default(0);

            $table->timestamps();

            $table->unique(['tenant_id', 'organization_unit_id', 'account_id', 'fiscal_period_id'], 'period_end_balances_account_period_uk');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('period_end_balances');
    }
};
