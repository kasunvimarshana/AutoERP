<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hr_employee_contacts', function (Blueprint $table): void {
            $table->id();
            $this->scope($table, 'hr_emp_contacts');
            $table->foreignId('employee_id')->constrained('hr_employees')->cascadeOnDelete();
            $table->string('contact_name');
            $table->string('relationship')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('mobile')->nullable();
            $table->boolean('is_emergency_contact')->default(false);
            $table->boolean('is_primary')->default(false);
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['tenant_id', 'employee_id'], 'hr_employee_contacts_scope_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hr_employee_contacts');
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
