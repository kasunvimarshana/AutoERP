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
        Schema::create('hr_biometric_raw_punches', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants', indexName: 'hr_bio_raw_punches_tenant_fk')->restrictOnDelete();
            $table->foreignId('device_id');
            $table->string('biometric_user_id', 64);
            $table->foreignId('employee_id')->nullable();
            $table->foreignId('attendance_punch_id')->nullable();
            $table->dateTime('punch_time');
            $table->dateTime('received_at');
            $table->string('punch_state', 32)->default('auto');
            $table->string('verify_method', 32)->default('unknown');
            $table->string('idempotency_hash', 64);
            $table->string('sync_status', 32)->default('pending');
            $table->string('error_message', 255)->nullable();
            $table->text('raw_payload')->nullable();
            $table->timestamps();

            $table->unique(['id', 'tenant_id'], 'hr_bio_raw_punches_id_tenant_uk');
            $table->unique(['tenant_id', 'idempotency_hash'], 'hr_bio_raw_punches_hash_uk');
            $table->index(['tenant_id', 'device_id', 'punch_time'], 'hr_bio_raw_punches_dev_time_ix');
            $table->index(['tenant_id', 'sync_status'], 'hr_bio_raw_punches_status_ix');
            $table->index(['tenant_id', 'employee_id', 'punch_time'], 'hr_bio_raw_punches_emp_time_ix');

            $table->foreign(['device_id', 'tenant_id'], 'hr_bio_raw_punches_dev_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('hr_biometric_devices')
                ->restrictOnDelete();
            $table->foreign(['employee_id', 'tenant_id'], 'hr_bio_raw_punches_emp_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('hr_employees')
                ->restrictOnDelete();
            $table->foreign('attendance_punch_id', 'hr_bio_raw_punches_att_punch_fk')
                ->references('id')
                ->on('hr_attendance_punches')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hr_biometric_raw_punches');
    }
};
