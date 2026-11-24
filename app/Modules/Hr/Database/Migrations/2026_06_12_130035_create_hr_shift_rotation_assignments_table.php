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
        Schema::create('hr_shift_rotation_assignments', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1);
            $table->foreignId('tenant_id')->constrained('tenants', indexName: 'hr_rot_assign_tenant_fk')->restrictOnDelete();
            $table->foreignId('organization_unit_id')->nullable();
            $table->unsignedBigInteger('employee_id');
            $table->unsignedBigInteger('shift_rotation_id');
            $table->date('anchor_date'); // cycle begins on this date
            $table->date('end_date')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['id', 'tenant_id'], 'hr_rot_assign_id_tenant_uk');
            $table->index(['tenant_id', 'employee_id', 'is_active'], 'hr_rot_assign_emp_ix');
            $table->index(['tenant_id', 'shift_rotation_id'], 'hr_rot_assign_rot_ix');

            $table->foreign(['employee_id', 'tenant_id'], 'hr_rot_assign_emp_fk')
                ->references(['id', 'tenant_id'])
                ->on('hr_employees')
                ->restrictOnDelete();

            $table->foreign(['shift_rotation_id', 'tenant_id'], 'hr_rot_assign_rot_fk')
                ->references(['id', 'tenant_id'])
                ->on('hr_shift_rotations')
                ->restrictOnDelete();

            $table->foreign(['organization_unit_id', 'tenant_id'], 'hr_rot_assign_org_unit_fk')
                ->references(['id', 'tenant_id'])
                ->on('organization_units')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hr_shift_rotation_assignments');
    }
};
