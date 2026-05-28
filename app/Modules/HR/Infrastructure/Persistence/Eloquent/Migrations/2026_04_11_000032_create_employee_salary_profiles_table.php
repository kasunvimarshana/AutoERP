<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_salary_profiles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('tenant_id')
                ->constrained('tenants', 'id')
                ->cascadeOnDelete()
                ->comment('Multi-tenant owner reference');
            $table->foreignId('organization_unit_id')
                ->nullable()
                ->constrained('organization_units', 'id')
                ->nullOnDelete()
                ->comment('Branch or department ownership');
            $table->json('metadata')->nullable()->comment('Extensible custom dynamic data');

            $table->foreignId('employee_id')->constrained('employees', 'id')->cascadeOnDelete();
            $table->string('salary_type', 40)->default('monthly');
            $table->decimal('basic_salary', 20, 4)->nullable();
            $table->decimal('hourly_rate', 20, 4)->nullable();
            $table->decimal('overtime_rate', 20, 4)->nullable();
            $table->unsignedBigInteger('payment_method_id')->nullable();
            $table->string('bank_account_reference', 150)->nullable();
            $table->date('effective_from')->nullable();
            $table->date('effective_to')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'employee_id', 'is_active'], 'employee_salary_profiles_active_idx');
            $table->index(['tenant_id', 'employee_id', 'effective_from'], 'employee_salary_profiles_effective_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_salary_profiles');
    }
};
