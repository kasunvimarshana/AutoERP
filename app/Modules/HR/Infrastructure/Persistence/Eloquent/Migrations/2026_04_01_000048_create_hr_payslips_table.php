<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hr_payslips', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units', 'id')->nullOnDelete();
            $table->json('metadata')->nullable();

            $table->foreignId('employee_id')->constrained('hr_employees')->cascadeOnDelete();
            $table->foreignId('payroll_run_id')->constrained('hr_payroll_runs')->cascadeOnDelete();
            $table->foreignId('salary_structure_id')->nullable()->constrained('hr_salary_structures')->nullOnDelete();
            $table->date('period_start');
            $table->date('period_end');
            $table->decimal('base_salary', 20, 4)->default(0);
            $table->decimal('total_earnings', 20, 4)->default(0);
            $table->decimal('total_deductions', 20, 4)->default(0);
            $table->decimal('net_salary', 20, 4)->default(0);
            $table->decimal('worked_days', 8, 2)->default(0);
            $table->decimal('leave_days_paid', 5, 2)->default(0);
            $table->decimal('leave_days_unpaid', 5, 2)->default(0);
            $table->decimal('overtime_hours', 8, 2)->default(0);
            $table->string('status', 20)->default('draft');
            $table->foreignId('journal_entry_id')->nullable()->constrained('journal_entries')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'organization_unit_id', 'payroll_run_id', 'employee_id'], 'hr_payslips_payroll_employee_uk');
            $table->index(['tenant_id', 'organization_unit_id', 'employee_id', 'period_end'], 'hr_payslips_employee_period_end_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hr_payslips');
    }
};
