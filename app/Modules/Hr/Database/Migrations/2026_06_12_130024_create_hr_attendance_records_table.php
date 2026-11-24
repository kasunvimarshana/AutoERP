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
        Schema::create('hr_attendance_records', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1);
            $table->foreignId('tenant_id')->constrained('tenants', indexName: 'hr_att_records_tenant_fk')->restrictOnDelete();
            $table->foreignId('organization_unit_id')->nullable();
            $table->foreignId('employee_id');
            $table->date('attendance_date');
            $table->foreignId('shift_id')->nullable();
            $table->dateTime('first_punch_in')->nullable();
            $table->dateTime('last_punch_out')->nullable();
            $table->unsignedSmallInteger('scheduled_minutes')->default(0);
            $table->unsignedSmallInteger('total_worked_minutes')->default(0);
            $table->unsignedSmallInteger('total_break_minutes')->default(0);
            $table->unsignedSmallInteger('unpaid_break_minutes')->default(0);
            $table->unsignedSmallInteger('regular_minutes')->default(0);
            $table->unsignedSmallInteger('overtime_minutes')->default(0);
            $table->unsignedSmallInteger('double_time_minutes')->default(0);
            $table->unsignedSmallInteger('late_arrival_minutes')->default(0);
            $table->unsignedSmallInteger('early_departure_minutes')->default(0);
            $table->string('status', 32)->default('unresolved');
            $table->string('lock_status', 32)->default('open');
            $table->boolean('is_regularized')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['id', 'tenant_id'], 'hr_att_records_id_tenant_uk');
            $table->unique(['tenant_id', 'employee_id', 'attendance_date'], 'hr_att_records_emp_date_uk');
            $table->index(['tenant_id', 'attendance_date', 'status'], 'hr_att_records_date_status_ix');
            $table->index(['tenant_id', 'lock_status'], 'hr_att_records_lock_ix');

            $table->foreign(['employee_id', 'tenant_id'], 'hr_att_records_employee_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('hr_employees')
                ->restrictOnDelete();
            $table->foreign(['shift_id', 'tenant_id'], 'hr_att_records_shift_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('hr_shifts')
                ->restrictOnDelete();
            $table->foreign(['organization_unit_id', 'tenant_id'], 'hr_att_records_org_unit_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('organization_units')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hr_attendance_records');
    }
};
