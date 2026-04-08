<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hr_performance_reviews', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('employee_id');
            $table->unsignedBigInteger('reviewer_id');
            $table->date('review_period_start');
            $table->date('review_period_end');
            $table->decimal('rating', 3, 1); // 1.0 - 5.0
            $table->text('comments')->nullable();
            $table->text('goals')->nullable();
            $table->text('achievements')->nullable();
            $table->string('status', 20)->default('draft'); // draft, submitted, acknowledged
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'employee_id']);
            $table->index(['tenant_id', 'reviewer_id']);
            $table->index('employee_id');
            $table->index('reviewer_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hr_performance_reviews');
    }
};
