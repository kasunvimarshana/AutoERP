<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();

            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('org_unit_id')->nullable()->constrained('org_units', 'id')->nullOnDelete();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');

            $table->foreignId('user_id')->unique('employees_user_id_uk')->constrained('users', 'id', 'employees_user_id_fk')->cascadeOnDelete();
            $table->string('employee_code')->nullable();
            $table->string('job_title')->nullable();
            $table->date('hire_date')->nullable();
            $table->date('termination_date')->nullable();
            $table->json('metadata')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->unique(['tenant_id', 'org_unit_id', 'employee_code'], 'employees_tenant_id_employee_code_uk');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
