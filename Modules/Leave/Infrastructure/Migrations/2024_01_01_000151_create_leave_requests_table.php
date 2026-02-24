<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('leave_requests', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->index();
            $table->uuid('employee_id')->index();
            $table->uuid('leave_type_id')->index();
            $table->date('start_date');
            $table->date('end_date');
            $table->unsignedInteger('days_requested');
            $table->text('reason')->nullable();
            $table->string('status')->default('draft');
            $table->uuid('reviewer_id')->nullable()->index();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_requests');
    }
};
