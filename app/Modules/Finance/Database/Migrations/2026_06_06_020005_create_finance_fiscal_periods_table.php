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
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units', 'id')->nullOnDelete();
            $table->foreignId('fiscal_year_id')->constrained('finance_fiscal_years', 'id')->cascadeOnDelete();
            $table->string('name');
            $table->unsignedSmallInteger('period_number');
            $table->date('start_date');
            $table->date('end_date');
            $table->enum('status', ['open', 'closed', 'locked'])->default('open');
            $table->timestamps();

            $table->index(['tenant_id', 'organization_unit_id'], 'finance_periods_tenant_org_idx');
            $table->index('fiscal_year_id', 'finance_periods_year_idx');
            $table->index('status', 'finance_periods_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_fiscal_periods');
    }
};
