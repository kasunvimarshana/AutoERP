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
        Schema::create('hr_attendance_punches', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants', indexName: 'hr_att_punches_tenant_fk')->restrictOnDelete();
            $table->foreignId('organization_unit_id')->nullable();
            $table->foreignId('attendance_record_id')->nullable();
            $table->foreignId('employee_id');
            $table->date('attendance_date');
            $table->dateTime('punch_time');
            $table->string('punch_type', 32);
            $table->string('punch_source', 32)->default('web');
            $table->string('capture_method', 32)->default('web');
            $table->unsignedBigInteger('credential_id')->nullable();
            $table->unsignedInteger('accuracy_meters')->nullable();
            $table->string('device_fingerprint', 128)->nullable();
            $table->string('photo_path', 255)->nullable();
            $table->unsignedBigInteger('geofence_id')->nullable();
            $table->string('idempotency_hash', 64)->nullable();
            $table->string('device_id', 64)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->boolean('is_verified')->default(true);
            $table->boolean('is_ignored')->default(false);
            $table->string('notes', 255)->nullable();
            $table->unsignedInteger('row_version')->default(1);
            $table->timestamps();

            $table->unique(['id', 'tenant_id'], 'hr_att_punches_id_tenant_uk');
            $table->index(['employee_id', 'attendance_date', 'punch_time'], 'hr_att_punches_emp_date_time_ix');
            $table->index(['attendance_record_id', 'is_ignored'], 'hr_att_punches_rec_ignore_ix');
            $table->index(['tenant_id', 'idempotency_hash'], 'hr_att_punches_tenant_hash_ix');

            $table->foreign(['attendance_record_id', 'tenant_id'], 'hr_att_punches_record_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('hr_attendance_records')
                ->restrictOnDelete();
            $table->foreign(['employee_id', 'tenant_id'], 'hr_att_punches_employee_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('hr_employees')
                ->restrictOnDelete();
            $table->foreign(['organization_unit_id', 'tenant_id'], 'hr_att_punches_org_unit_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('organization_units')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hr_attendance_punches');
    }
};
