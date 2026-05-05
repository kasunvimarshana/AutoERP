<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hr_leave_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('org_unit_id')->nullable()->constrained('org_units', 'id')->nullOnDelete();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->unsignedBigInteger('employee_id');
            $table->foreignId('leave_type_id')->constrained('hr_leave_types', 'id', 'hr_leave_balances_leave_type_id_fk')->cascadeOnDelete();
            $table->unsignedSmallInteger('year');
            $table->decimal('allocated', 8, 2)->default(0);
            $table->decimal('used', 8, 2)->default(0);
            $table->decimal('pending', 8, 2)->default(0);
            $table->decimal('carried', 8, 2)->default(0);
            $table->foreign('employee_id', 'hr_leave_balances_employee_id_fk')
                ->references('id')->on('employees')->cascadeOnDelete();
            $table->timestamps();

            $table->index(['tenant_id'], 'hr_leave_balances_tenant_id_idx');
            $table->index(['employee_id'], 'hr_leave_balances_employee_id_idx');
            $table->unique(['tenant_id', 'employee_id', 'leave_type_id', 'year'], 'hr_leave_balances_unique_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hr_leave_balances');
    }
};
