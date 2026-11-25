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
        Schema::create('hr_employee_attendance_credentials', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1);
            $table->foreignId('tenant_id')->constrained('tenants', indexName: 'hr_emp_att_creds_tenant_fk')->restrictOnDelete();
            $table->foreignId('organization_unit_id')->nullable();
            $table->foreignId('employee_id');
            $table->string('credential_type', 32);
            $table->string('credential_value', 128);
            $table->string('label', 120)->nullable();
            $table->string('status', 32)->default('active');
            $table->dateTime('issued_at')->nullable();
            $table->dateTime('expires_at')->nullable();
            $table->dateTime('revoked_at')->nullable();
            $table->string('revocation_reason', 255)->nullable();
            $table->timestamps();

            $table->unique(['id', 'tenant_id'], 'hr_emp_att_creds_id_tenant_uk');
            $table->unique(['tenant_id', 'credential_type', 'credential_value'], 'hr_emp_att_creds_val_uk');
            $table->index(['tenant_id', 'employee_id', 'status'], 'hr_emp_att_creds_emp_status_ix');

            $table->foreign(['employee_id', 'tenant_id'], 'hr_emp_att_creds_emp_fk')
                ->references(['id', 'tenant_id'])
                ->on('hr_employees')
                ->restrictOnDelete();
            $table->foreign(['organization_unit_id', 'tenant_id'], 'hr_emp_att_creds_org_unit_fk')
                ->references(['id', 'tenant_id'])
                ->on('organization_units')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hr_employee_attendance_credentials');
    }
};
