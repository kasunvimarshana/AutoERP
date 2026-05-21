<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete()->comment('Multi-tenant owner reference');
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units', 'id')->nullOnDelete()->comment('Branch or department ownership');
            $table->json('metadata')->nullable()->comment('Extensible custom dynamic data');

            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('biometric_device_id')->nullable()->constrained('biometric_devices')->nullOnDelete();
            $table->timestamp('punch_time');
            $table->string('punch_type')->default('in')->comment('in,out');
            $table->string('source')->default('manual');
            $table->json('raw_data')->nullable();
            $table->timestamp('processed_at')->nullable();

            $table->timestamps();

            $table->index(['tenant_id', 'employee_id', 'punch_time'], 'attendance_logs_employee_punch_time_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_logs');
    }
};
