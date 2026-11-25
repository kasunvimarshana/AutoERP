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
        Schema::create('hr_attendance_regularizations', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1);
            $table->foreignId('tenant_id')->constrained('tenants', indexName: 'hr_att_reg_tenant_fk')->restrictOnDelete();
            $table->foreignId('organization_unit_id')->nullable();
            $table->foreignId('attendance_record_id')->nullable();
            $table->foreignId('employee_id');
            $table->date('attendance_date');
            $table->string('regularization_type', 32);
            $table->dateTime('proposed_clock_in')->nullable();
            $table->dateTime('proposed_clock_out')->nullable();
            $table->string('reason_code', 32)->default('forgot_to_punch');
            $table->text('employee_notes');
            $table->string('status', 32)->default('pending');
            $table->unsignedBigInteger('actioned_by')->nullable();
            $table->timestamp('actioned_at')->nullable();
            $table->text('action_notes')->nullable();
            $table->timestamps();

            $table->unique(['id', 'tenant_id'], 'hr_att_reg_id_tenant_uk');
            $table->index(['employee_id', 'attendance_date', 'status'], 'hr_att_reg_emp_date_status_ix');
            $table->index(['tenant_id', 'status'], 'hr_att_reg_tenant_status_ix');

            $table->foreign(['attendance_record_id', 'tenant_id'], 'hr_att_reg_record_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('hr_attendance_records')
                ->restrictOnDelete();
            $table->foreign(['employee_id', 'tenant_id'], 'hr_att_reg_employee_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('hr_employees')
                ->restrictOnDelete();
            $table->foreign(['organization_unit_id', 'tenant_id'], 'hr_att_reg_org_unit_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('organization_units')
                ->restrictOnDelete();
            $table->foreign(['actioned_by', 'tenant_id'], 'hr_att_reg_user_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('users')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hr_attendance_regularizations');
    }
};
