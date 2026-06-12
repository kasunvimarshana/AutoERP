<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hr_employee_availabilities', function (Blueprint $table): void {
            $table->id();
            $this->scope($table, 'hr_emp_availability');
            $table->foreignId('employee_id')->constrained('hr_employees')->cascadeOnDelete();
            $table->date('availability_date')->nullable();
            $table->string('availability_status');
            $table->string('source_type')->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->text('reason')->nullable();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamps();
            $table->index(['tenant_id', 'employee_id', 'availability_status'], 'hr_employee_availability_lookup_idx');
            $table->index(['source_type', 'source_id'], 'hr_employee_availability_source_idx');
            $table->index(['starts_at', 'ends_at'], 'hr_employee_availability_dates_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hr_employee_availabilities');
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
