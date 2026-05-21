<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_records', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete()->comment('Multi-tenant owner reference');
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units', 'id')->nullOnDelete()->comment('Branch or department ownership');
            $table->json('metadata')->nullable()->comment('Extensible custom dynamic data');

            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->date('attendance_date');
            $table->timestamp('check_in')->nullable();
            $table->timestamp('check_out')->nullable();
            $table->integer('break_duration')->default(0);
            $table->integer('worked_minutes')->default(0);
            $table->integer('overtime_minutes')->default(0);
            $table->string('status')->default('present');
            $table->foreignId('shift_id')->nullable()->constrained('shifts')->nullOnDelete();
            $table->text('remarks')->nullable();

            $table->timestamps();

            $table->unique(['tenant_id', 'employee_id', 'attendance_date'], 'attendance_records_employee_date_uk');
            $table->index(['tenant_id', 'attendance_date'], 'attendance_records_date_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_records');
    }
};
