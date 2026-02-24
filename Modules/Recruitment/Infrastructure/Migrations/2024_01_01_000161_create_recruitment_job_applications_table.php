<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('recruitment_job_applications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->index();
            $table->uuid('position_id')->index();
            $table->string('candidate_name');
            $table->string('email');
            $table->string('phone')->nullable();
            $table->string('resume_url')->nullable();
            $table->text('cover_letter')->nullable();
            $table->string('source')->nullable();
            $table->string('status')->default('new');
            $table->uuid('reviewer_id')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recruitment_job_applications');
    }
};
