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
            $table->foreignId('tenant_id');
            $table->foreign('tenant_id', 'hr_employee_skill_assignments_tenant_fk')
                ->references('id')
                ->on('tenants')
                ->restrictOnDelete();
            $table->foreignId('organization_unit_id')->nullable();
            $table->foreign(['organization_unit_id', 'tenant_id'], 'hr_employee_skill_assignments_org_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('organization_units')
                ->restrictOnDelete();
            $table->foreignId('employee_id');
            $table->foreignId('skill_id');
            $table->string('proficiency_level');
            $table->decimal('years_of_experience', 20, 6)->default('0.000000');
            $table->boolean('is_primary')->default(false);
            $table->timestamps();
            $table->unique(['employee_id', 'skill_id'], 'hr_employee_skills_uk');
            $table->index(['tenant_id', 'skill_id'], 'hr_employee_skills_lookup_ix');

            $table->unique(['id', 'tenant_id'], 'hr_employee_skill_assignments_id_tenant_uk');
            $table->foreign(['employee_id', 'tenant_id'], 'hr_employee_skill_assignments_employee_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('hr_employees')
                ->cascadeOnDelete();
            $table->foreign(['skill_id', 'tenant_id'], 'hr_employee_skill_assignments_skill_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('hr_skills')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hr_employee_skill_assignments');
    }

};
