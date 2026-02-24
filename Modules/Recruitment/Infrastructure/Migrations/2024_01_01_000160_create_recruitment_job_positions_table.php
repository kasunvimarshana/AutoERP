<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('recruitment_job_positions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->index();
            $table->string('title');
            $table->uuid('department_id')->nullable()->index();
            $table->string('location')->nullable();
            $table->string('employment_type')->default('full_time');
            $table->text('description')->nullable();
            $table->text('requirements')->nullable();
            $table->unsignedInteger('vacancies')->default(1);
            $table->string('status')->default('open');
            $table->date('expected_start_date')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recruitment_job_positions');
    }
};
