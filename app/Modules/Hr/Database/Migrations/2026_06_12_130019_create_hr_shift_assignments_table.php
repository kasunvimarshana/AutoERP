<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hr_shift_assignments', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1);
            $table->foreignId('tenant_id')->constrained('tenants', indexName: 'hr_shift_assignments_tenant_fk')->restrictOnDelete();
            $table->foreignId('organization_unit_id')->nullable();
            $table->foreignId('employee_id');
            $table->foreignId('shift_id');
            $table->string('assignment_type', 32)->default('recurring');
            $table->unsignedTinyInteger('day_of_week')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->boolean('is_custom_timing')->default(false);
            $table->time('custom_start_time')->nullable();
            $table->time('custom_end_time')->nullable();
            $table->boolean('is_cancelled')->default(false);
            $table->text('cancellation_reason')->nullable();
            $table->unsignedBigInteger('assigned_by')->nullable();
            $table->timestamp('assigned_at')->nullable();
            $table->timestamps();

            $table->unique(['id', 'tenant_id'], 'hr_shift_assignments_id_tenant_uk');
            $table->index(['employee_id', 'assignment_type', 'is_cancelled'], 'hr_shift_assign_emp_type_ix');
            $table->index(['employee_id', 'start_date', 'end_date'], 'hr_shift_assign_date_range_ix');
            $table->index(['tenant_id', 'shift_id'], 'hr_shift_assign_tenant_shift_ix');

            $table->foreign(['employee_id', 'tenant_id'], 'hr_shift_assignments_employee_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('hr_employees')
                ->restrictOnDelete();
            $table->foreign(['shift_id', 'tenant_id'], 'hr_shift_assignments_shift_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('hr_shifts')
                ->restrictOnDelete();
            $table->foreign(['organization_unit_id', 'tenant_id'], 'hr_shift_assignments_org_unit_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('organization_units')
                ->restrictOnDelete();
            $table->foreign(['assigned_by', 'tenant_id'], 'hr_shift_assignments_assigned_by_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('users')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hr_shift_assignments');
    }
};
