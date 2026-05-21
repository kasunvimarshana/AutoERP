<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leave_allocations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete()->comment('Multi-tenant owner reference');
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units', 'id')->nullOnDelete()->comment('Branch or department ownership');
            $table->json('metadata')->nullable()->comment('Extensible custom dynamic data');

            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('leave_type_id')->constrained('leave_types');
            $table->unsignedSmallInteger('year');
            $table->decimal('allocated_days', 20, 4)->default(0);
            $table->decimal('used_days', 20, 4)->default(0);
            $table->decimal('pending_days', 20, 4)->default(0);
            $table->decimal('carried_forward', 20, 4)->default(0);
            $table->date('expiry_date')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();

            $table->timestamps();

            $table->unique(['tenant_id', 'employee_id', 'leave_type_id', 'year'], 'leave_allocations_employee_type_year_uk');
            $table->index(['tenant_id', 'employee_id', 'year'], 'leave_allocations_employee_year_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_allocations');
    }
};
