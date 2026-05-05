<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hr_leave_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants', 'id', 'hr_leave_requests_tenant_id_fk')->cascadeOnDelete();
            $table->unsignedBigInteger('employee_id');
            $table->foreignId('leave_type_id')->constrained('hr_leave_types', 'id', 'hr_leave_requests_leave_type_id_fk')->cascadeOnDelete();
            $table->date('start_date');
            $table->date('end_date');
            $table->decimal('total_days', 5, 2)->default(0);
            $table->text('reason')->nullable();
            $table->string('status', 20)->default('pending');
            $table->unsignedBigInteger('approver_id')->nullable();
            $table->text('approver_note')->nullable();
            $table->string('attachment_path')->nullable();
            $table->json('metadata')->nullable();
            $table->foreign('employee_id', 'hr_leave_requests_employee_id_fk')
                ->references('id')->on('employees')->cascadeOnDelete();
            $table->timestamps();

            $table->index(['tenant_id'], 'hr_leave_requests_tenant_id_idx');
            $table->index(['employee_id'], 'hr_leave_requests_employee_id_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hr_leave_requests');
    }
};
