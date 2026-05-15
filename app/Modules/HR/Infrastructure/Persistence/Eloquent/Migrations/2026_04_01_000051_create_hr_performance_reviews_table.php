<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hr_performance_reviews', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units', 'id')->nullOnDelete();
            $table->json('metadata')->nullable();

            $table->foreignId('employee_id')->constrained('hr_employees')->cascadeOnDelete();
            $table->foreignId('cycle_id')->constrained('hr_performance_cycles')->cascadeOnDelete();
            $table->foreignId('reviewer_id')->constrained('users');
            $table->string('overall_rating', 30)->nullable();
            $table->json('goals')->nullable();
            $table->text('strengths')->nullable();
            $table->text('improvements')->nullable();
            $table->text('reviewer_comments')->nullable();
            $table->text('employee_comments')->nullable();
            $table->string('status', 20)->default('pending');
            $table->timestamp('acknowledged_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'organization_unit_id', 'cycle_id', 'status'], 'hr_performance_reviews_cycle_status_idx');
            $table->index(['tenant_id', 'organization_unit_id', 'employee_id'], 'hr_performance_reviews_employee_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hr_performance_reviews');
    }
};
