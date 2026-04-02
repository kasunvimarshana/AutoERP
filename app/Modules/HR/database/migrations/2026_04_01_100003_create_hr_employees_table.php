<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hr_employees', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->string('first_name', 100);
            $table->string('last_name', 100);
            $table->string('email', 255);
            $table->string('phone', 50)->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('gender', 20)->nullable();
            $table->text('address')->nullable();
            $table->string('employee_number', 50);
            $table->date('hire_date');
            $table->string('employment_type', 50)->default('full_time');
            $table->string('status', 50)->default('active');
            $table->unsignedBigInteger('department_id')->nullable();
            $table->unsignedBigInteger('position_id')->nullable();
            $table->unsignedBigInteger('manager_id')->nullable();
            $table->decimal('salary', 12, 2)->nullable();
            $table->string('currency', 3)->default('USD');
            $table->unsignedBigInteger('org_unit_id')->nullable();
            $table->json('metadata')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index('tenant_id');
            $table->index('department_id');
            $table->index('status');
            $table->index('employment_type');
            $table->index('email');
            $table->unique(['employee_number', 'tenant_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hr_employees');
    }
};
