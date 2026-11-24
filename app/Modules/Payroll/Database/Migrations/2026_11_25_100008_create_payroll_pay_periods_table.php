<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_pay_periods', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1);
            $table->foreignId('tenant_id')->constrained('tenants', indexName: 'payroll_pay_periods_tenant_fk')->restrictOnDelete();
            $table->foreignId('organization_unit_id')->nullable();
            $table->string('name', 100);
            $table->string('cycle_type', 32)->default('monthly'); // monthly, bi_weekly, weekly
            $table->date('period_start');
            $table->date('period_end');
            $table->date('payout_date');
            $table->unsignedSmallInteger('total_working_days')->default(22);
            $table->boolean('is_closed')->default(false);
            $table->timestamps();

            $table->unique(['id', 'tenant_id'], 'payroll_pay_periods_id_tenant_uk');
            $table->unique(['tenant_id', 'period_start', 'period_end', 'cycle_type'], 'payroll_pay_periods_cycle_dates_uk');
            $table->index(['tenant_id', 'is_closed'], 'payroll_pay_periods_closed_ix');
            $table->index(['tenant_id', 'payout_date'], 'payroll_pay_periods_payout_ix');

            $table->foreign(['organization_unit_id', 'tenant_id'], 'payroll_pay_periods_org_unit_fk')
                ->references(['id', 'tenant_id'])
                ->on('organization_units')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_pay_periods');
    }
};
