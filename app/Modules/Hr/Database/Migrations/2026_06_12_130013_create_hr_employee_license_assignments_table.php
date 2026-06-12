<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hr_employee_license_assignments', function (Blueprint $table): void {
            $table->id();
            $this->scope($table, 'hr_emp_licenses');
            $table->foreignId('employee_id')->constrained('hr_employees')->cascadeOnDelete();
            $table->foreignId('license_id')->constrained('hr_licenses')->cascadeOnDelete();
            $table->string('license_number')->nullable();
            $table->date('issued_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->string('status')->default('pending');
            $table->timestamps();
            $table->unique(['employee_id', 'license_id'], 'hr_employee_licenses_uk');
            $table->index(['tenant_id', 'license_id'], 'hr_employee_licenses_lookup_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hr_employee_license_assignments');
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
