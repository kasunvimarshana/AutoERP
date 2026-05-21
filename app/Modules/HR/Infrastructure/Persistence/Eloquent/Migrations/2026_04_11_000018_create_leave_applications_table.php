<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leave_applications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete()->comment('Multi-tenant owner reference');
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units', 'id')->nullOnDelete()->comment('Branch or department ownership');
            $table->json('metadata')->nullable()->comment('Extensible custom dynamic data');

            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('leave_type_id')->constrained('leave_types');
            $table->date('start_date');
            $table->date('end_date');
            $table->decimal('total_days', 20, 4)->default(0);
            $table->string('half_day_type')->nullable();
            $table->text('reason')->nullable();
            $table->string('status')->default('pending');
            $table->unsignedBigInteger('approver_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('approver_note')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->string('attachment_path')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'employee_id', 'status'], 'leave_applications_employee_status_idx');
            $table->index(['tenant_id', 'status', 'start_date'], 'leave_applications_status_date_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_applications');
    }
};
