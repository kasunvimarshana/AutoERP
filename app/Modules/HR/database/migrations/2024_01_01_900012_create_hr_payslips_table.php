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
            $table->foreignId('tenant_id')->constrained('tenants', 'id', 'hr_payslips_tenant_id_fk')->cascadeOnDelete();
            $table->unsignedBigInteger('employee_id');
            $table->foreignId('payroll_run_id')->constrained('hr_payroll_runs', 'id', 'hr_payslips_payroll_run_id_fk')->cascadeOnDelete();
            $table->date('period_start');
            $table->date('period_end');
            $table->decimal('gross_salary', 20, 6)->default(0);
            $table->decimal('total_deductions', 20, 6)->default(0);
            $table->decimal('net_salary', 20, 6)->default(0);
            $table->decimal('base_salary', 20, 6)->default(0);
            $table->decimal('worked_days', 8, 2)->default(0);
            $table->string('status', 20)->default('draft');
            $table->unsignedBigInteger('journal_entry_id')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id'], 'hr_payslips_tenant_id_idx');
            $table->index(['employee_id'], 'hr_payslips_employee_id_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hr_payslips');
    }
};
