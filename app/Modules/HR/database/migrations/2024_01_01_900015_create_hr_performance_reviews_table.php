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
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('org_unit_id')->nullable()->constrained('org_units', 'id')->nullOnDelete();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->unsignedBigInteger('employee_id');
            $table->foreignId('cycle_id')->constrained('hr_performance_cycles', 'id', 'hr_performance_reviews_cycle_id_fk')->cascadeOnDelete();
            $table->unsignedBigInteger('reviewer_id');
            $table->string('overall_rating', 30)->nullable();
            $table->json('goals')->nullable();
            $table->text('strengths')->nullable();
            $table->text('improvements')->nullable();
            $table->text('reviewer_comments')->nullable();
            $table->text('employee_comments')->nullable();
            $table->string('status', 20)->default('pending');
            $table->timestamp('acknowledged_at')->nullable();
            $table->json('metadata')->nullable();
            $table->foreign('employee_id', 'hr_performance_reviews_employee_id_fk')
                ->references('id')->on('employees')->cascadeOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id'], 'hr_performance_reviews_tenant_id_idx');
            $table->index(['employee_id'], 'hr_performance_reviews_employee_id_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hr_performance_reviews');
    }
};
