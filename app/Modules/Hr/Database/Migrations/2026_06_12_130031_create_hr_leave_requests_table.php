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
        Schema::create('hr_leave_requests', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1);
            $table->foreignId('tenant_id')->constrained('tenants', indexName: 'hr_leave_req_tenant_fk')->restrictOnDelete();
            $table->foreignId('organization_unit_id')->nullable();
            $table->foreignId('employee_id');
            $table->foreignId('leave_type_id');
            $table->date('start_date');
            $table->date('end_date');
            $table->string('duration_type', 32)->default('full_day');
            $table->decimal('total_days', 5, 2);
            $table->text('reason');
            $table->foreignId('attachment_document_id')->nullable();
            $table->string('status', 32)->default('pending');
            $table->unsignedBigInteger('approver_id')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->text('action_notes')->nullable();
            $table->timestamps();

            $table->unique(['id', 'tenant_id'], 'hr_leave_req_id_tenant_uk');
            $table->index(['employee_id', 'status'], 'hr_leave_req_emp_status_ix');
            $table->index(['start_date', 'end_date'], 'hr_leave_req_dates_ix');
            $table->index(['tenant_id', 'status'], 'hr_leave_req_tenant_status_ix');

            $table->foreign(['employee_id', 'tenant_id'], 'hr_leave_req_emp_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('hr_employees')
                ->restrictOnDelete();
            $table->foreign(['leave_type_id', 'tenant_id'], 'hr_leave_req_type_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('hr_leave_types')
                ->restrictOnDelete();
            $table->foreign(['attachment_document_id', 'tenant_id'], 'hr_leave_req_doc_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('hr_employee_documents')
                ->nullOnDelete();
            $table->foreign(['approver_id', 'tenant_id'], 'hr_leave_req_appr_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('users')
                ->restrictOnDelete();
            $table->foreign(['organization_unit_id', 'tenant_id'], 'hr_leave_req_org_unit_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('organization_units')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hr_leave_requests');
    }
};
