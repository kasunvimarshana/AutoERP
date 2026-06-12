<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hr_employee_skill_assignments', function (Blueprint $table): void {
            $table->id();
            $this->scope($table, 'hr_emp_skills');
            $table->foreignId('employee_id')->constrained('hr_employees')->cascadeOnDelete();
            $table->foreignId('skill_id')->constrained('hr_skills')->cascadeOnDelete();
            $table->string('proficiency_level');
            $table->decimal('years_of_experience', 20, 6)->default('0.000000');
            $table->boolean('is_primary')->default(false);
            $table->timestamps();
            $table->unique(['employee_id', 'skill_id'], 'hr_employee_skills_uk');
            $table->index(['tenant_id', 'skill_id'], 'hr_employee_skills_lookup_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hr_employee_skill_assignments');
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
