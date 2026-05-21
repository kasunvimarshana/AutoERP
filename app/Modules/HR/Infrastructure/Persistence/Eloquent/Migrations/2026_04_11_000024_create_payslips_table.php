<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payslips', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete()->comment('Multi-tenant owner reference');
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units', 'id')->nullOnDelete()->comment('Branch or department ownership');
            $table->json('metadata')->nullable()->comment('Extensible custom dynamic data');

            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('payroll_run_id')->constrained('payroll_runs')->cascadeOnDelete();
            $table->foreignId('salary_structure_id')->nullable()->constrained('salary_structures')->nullOnDelete();
            $table->date('period_start');
            $table->date('period_end');
            $table->decimal('base_salary', 20, 4)->default(0);
            $table->decimal('total_earnings', 20, 4)->default(0);
            $table->decimal('total_deductions', 20, 4)->default(0);
            $table->decimal('net_salary', 20, 4)->default(0);
            $table->decimal('worked_days', 20, 4)->default(0);
            $table->decimal('leave_days_paid', 20, 4)->default(0);
            $table->decimal('leave_days_unpaid', 20, 4)->default(0);
            $table->decimal('overtime_hours', 20, 4)->default(0);
            $table->string('status')->default('draft');
            $table->foreignId('journal_entry_id')->nullable()->constrained('journal_entries')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'payroll_run_id', 'employee_id'], 'payslips_payroll_employee_uk');
            $table->index(['tenant_id', 'employee_id', 'period_end'], 'payslips_employee_period_end_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payslips');
    }
};
