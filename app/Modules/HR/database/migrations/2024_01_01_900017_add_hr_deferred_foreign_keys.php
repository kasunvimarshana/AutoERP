<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('employees')) {
            return;
        }

        Schema::table('hr_shift_assignments', function (Blueprint $table) {
            $table->foreign('employee_id', 'hr_shift_assignments_employee_id_fk')
                ->references('id')->on('employees')->cascadeOnDelete();
        });

        Schema::table('hr_leave_balances', function (Blueprint $table) {
            $table->foreign('employee_id', 'hr_leave_balances_employee_id_fk')
                ->references('id')->on('employees')->cascadeOnDelete();
        });

        Schema::table('hr_leave_requests', function (Blueprint $table) {
            $table->foreign('employee_id', 'hr_leave_requests_employee_id_fk')
                ->references('id')->on('employees')->cascadeOnDelete();
        });

        Schema::table('hr_attendance_logs', function (Blueprint $table) {
            $table->foreign('employee_id', 'hr_attendance_logs_employee_id_fk')
                ->references('id')->on('employees')->cascadeOnDelete();
        });

        Schema::table('hr_attendance_records', function (Blueprint $table) {
            $table->foreign('employee_id', 'hr_attendance_records_employee_id_fk')
                ->references('id')->on('employees')->cascadeOnDelete();
        });

        Schema::table('hr_payslips', function (Blueprint $table) {
            $table->foreign('employee_id', 'hr_payslips_employee_id_fk')
                ->references('id')->on('employees')->cascadeOnDelete();
        });

        Schema::table('hr_performance_reviews', function (Blueprint $table) {
            $table->foreign('employee_id', 'hr_performance_reviews_employee_id_fk')
                ->references('id')->on('employees')->cascadeOnDelete();
        });

        Schema::table('hr_employee_documents', function (Blueprint $table) {
            $table->foreign('employee_id', 'hr_employee_documents_employee_id_fk')
                ->references('id')->on('employees')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('employees')) {
            return;
        }

        Schema::table('hr_shift_assignments', function (Blueprint $table) {
            $table->dropForeign('hr_shift_assignments_employee_id_fk');
        });
        Schema::table('hr_leave_balances', function (Blueprint $table) {
            $table->dropForeign('hr_leave_balances_employee_id_fk');
        });
        Schema::table('hr_leave_requests', function (Blueprint $table) {
            $table->dropForeign('hr_leave_requests_employee_id_fk');
        });
        Schema::table('hr_attendance_logs', function (Blueprint $table) {
            $table->dropForeign('hr_attendance_logs_employee_id_fk');
        });
        Schema::table('hr_attendance_records', function (Blueprint $table) {
            $table->dropForeign('hr_attendance_records_employee_id_fk');
        });
        Schema::table('hr_payslips', function (Blueprint $table) {
            $table->dropForeign('hr_payslips_employee_id_fk');
        });
        Schema::table('hr_performance_reviews', function (Blueprint $table) {
            $table->dropForeign('hr_performance_reviews_employee_id_fk');
        });
        Schema::table('hr_employee_documents', function (Blueprint $table) {
            $table->dropForeign('hr_employee_documents_employee_id_fk');
        });
    }
};
