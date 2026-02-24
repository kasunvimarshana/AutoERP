<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('hr_payslips', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->index();
            $table->uuid('payroll_run_id')->index();
            $table->uuid('employee_id')->index();
            $table->decimal('gross_salary', 18, 8);
            $table->decimal('deductions', 18, 8)->default(0);
            $table->decimal('net_salary', 18, 8);
            $table->string('status')->default('pending');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hr_payslips');
    }
};
