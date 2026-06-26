<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finance_fiscal_periods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants', 'id', indexName: 'finance_fiscal_periods_tenant_fk')->restrictOnDelete();
            $table->foreignId('organization_unit_id')->nullable();
            $table->foreignId('fiscal_year_id');
            $table->string('name');
            $table->unsignedSmallInteger('period_number');
            $table->date('start_date');
            $table->date('end_date');
            $table->string('status', 30)->default('open');
            $table->timestamps();

            $table->unique(['fiscal_year_id', 'period_number'], 'finance_fiscal_periods_year_number_uk');
            $table->index(['tenant_id', 'organization_unit_id'], 'finance_periods_tenant_org_ix');
            $table->index(['tenant_id', 'organization_unit_id', 'start_date', 'end_date'], 'finance_fiscal_periods_scope_dates_ix', );
            $table->index('fiscal_year_id', 'finance_periods_year_ix');
            $table->index('status', 'finance_periods_status_ix');

            $table->unique(['id', 'tenant_id'], 'finance_fiscal_periods_id_tenant_uk');
            $table->foreign(['organization_unit_id', 'tenant_id'], 'finance_fiscal_periods_organization_unit_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('organization_units')
                ->restrictOnDelete();
            $table->foreign(['fiscal_year_id', 'tenant_id'], 'finance_fiscal_periods_fiscal_year_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('finance_fiscal_years')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_fiscal_periods');
    }
};
