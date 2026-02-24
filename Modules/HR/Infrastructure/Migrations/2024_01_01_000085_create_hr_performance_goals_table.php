<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hr_performance_goals', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('tenant_id');
            $table->string('employee_id');
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('period');
            $table->unsignedSmallInteger('year')->nullable();
            $table->date('due_date')->nullable();
            $table->string('status')->default('active');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'employee_id']);
            $table->index(['tenant_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hr_performance_goals');
    }
};
