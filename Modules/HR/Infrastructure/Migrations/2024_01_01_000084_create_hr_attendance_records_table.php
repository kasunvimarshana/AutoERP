<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('hr_attendance_records', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->index();
            $table->uuid('employee_id')->index();
            $table->date('work_date');
            $table->dateTime('check_in')->nullable();
            $table->dateTime('check_out')->nullable();
            $table->decimal('duration_hours', 8, 2)->nullable();
            $table->string('status')->default('present');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['tenant_id', 'employee_id', 'work_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hr_attendance_records');
    }
};
