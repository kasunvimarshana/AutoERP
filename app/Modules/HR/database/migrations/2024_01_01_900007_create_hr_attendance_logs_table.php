<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hr_attendance_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants', 'id', 'hr_attendance_logs_tenant_id_fk')->cascadeOnDelete();
            $table->unsignedBigInteger('employee_id');
            $table->unsignedBigInteger('biometric_device_id')->nullable();
            $table->timestamp('punch_time');
            $table->string('punch_type', 20)->default('in');
            $table->string('source', 50)->default('manual');
            $table->json('raw_data')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id'], 'hr_attendance_logs_tenant_id_idx');
            $table->index(['employee_id'], 'hr_attendance_logs_employee_id_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hr_attendance_logs');
    }
};
