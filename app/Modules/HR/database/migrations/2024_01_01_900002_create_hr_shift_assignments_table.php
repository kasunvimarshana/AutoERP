<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hr_shift_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants', 'id', 'hr_shift_assignments_tenant_id_fk')->cascadeOnDelete();
            $table->unsignedBigInteger('employee_id');
            $table->foreignId('shift_id')->constrained('hr_shifts', 'id', 'hr_shift_assignments_shift_id_fk')->cascadeOnDelete();
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->foreign('employee_id', 'hr_shift_assignments_employee_id_fk')
                ->references('id')->on('employees')->cascadeOnDelete();
            $table->timestamps();

            $table->index(['tenant_id'], 'hr_shift_assignments_tenant_id_idx');
            $table->index(['employee_id'], 'hr_shift_assignments_employee_id_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hr_shift_assignments');
    }
};
