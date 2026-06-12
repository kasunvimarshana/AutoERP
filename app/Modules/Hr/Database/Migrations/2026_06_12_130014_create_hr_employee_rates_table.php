<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hr_employee_rates', function (Blueprint $table): void {
            $table->id();
            $this->scope($table, 'hr_emp_rates');
            $table->foreignId('employee_id')->constrained('hr_employees')->cascadeOnDelete();
            $table->string('rate_type');
            $table->decimal('amount', 20, 6);
            $table->foreignId('currency_id')->nullable()->constrained('currencies')->nullOnDelete();
            $table->date('effective_from')->nullable();
            $table->date('effective_to')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['tenant_id', 'employee_id', 'rate_type'], 'hr_employee_rates_lookup_idx');
            $table->index(['effective_from', 'effective_to'], 'hr_employee_rates_dates_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hr_employee_rates');
    }

    private function scope(Blueprint $table, string $constraintPrefix): void
    {
        $table->foreignId('tenant_id');
        $table->foreign('tenant_id', $constraintPrefix.'_tenant_fk')
            ->references('id')
            ->on('tenants')
            ->cascadeOnDelete();
        $table->foreignId('organization_unit_id')->nullable();
        $table->foreign('organization_unit_id', $constraintPrefix.'_org_fk')
            ->references('id')
            ->on('organization_units')
            ->nullOnDelete();
    }
};
