<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hr_salary_structure_assignments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('tenant_id');
            $table->uuid('employee_id');
            $table->uuid('structure_id');
            $table->decimal('base_amount', 18, 8)->default(0);
            $table->date('effective_from');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'employee_id']);
            $table->index(['tenant_id', 'employee_id', 'effective_from']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hr_salary_structure_assignments');
    }
};
