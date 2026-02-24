<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('pm_tasks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->index();
            $table->uuid('project_id')->index();
            $table->string('title');
            $table->text('description')->nullable();
            $table->uuid('assigned_to')->nullable();
            $table->string('status')->default('todo');
            $table->string('priority')->default('medium');
            $table->date('due_date')->nullable();
            $table->decimal('estimated_hours', 10, 2)->default(0);
            $table->decimal('actual_hours', 10, 2)->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pm_tasks');
    }
};
