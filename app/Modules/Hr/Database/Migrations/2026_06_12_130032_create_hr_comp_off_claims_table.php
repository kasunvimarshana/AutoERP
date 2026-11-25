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
        Schema::create('hr_comp_off_claims', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1);
            $table->foreignId('tenant_id')->constrained('tenants', indexName: 'hr_comp_off_tenant_fk')->restrictOnDelete();
            $table->foreignId('organization_unit_id')->nullable();
            $table->foreignId('employee_id');
            $table->date('worked_date');
            $table->foreignId('attendance_record_id')->nullable();
            $table->decimal('claimed_days', 5, 2)->default('1.00');
            $table->text('reason');
            $table->string('status', 32)->default('pending');
            $table->date('expiry_date')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->unique(['id', 'tenant_id'], 'hr_comp_off_id_tenant_uk');
            $table->index(['employee_id', 'worked_date'], 'hr_comp_off_emp_date_ix');
            $table->index(['tenant_id', 'status'], 'hr_comp_off_tenant_status_ix');

            $table->foreign(['employee_id', 'tenant_id'], 'hr_comp_off_emp_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('hr_employees')
                ->restrictOnDelete();
            $table->foreign(['attendance_record_id', 'tenant_id'], 'hr_comp_off_rec_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('hr_attendance_records')
                ->restrictOnDelete();
            $table->foreign(['approved_by', 'tenant_id'], 'hr_comp_off_appr_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('users')
                ->restrictOnDelete();
            $table->foreign(['organization_unit_id', 'tenant_id'], 'hr_comp_off_org_unit_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('organization_units')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hr_comp_off_claims');
    }
};
