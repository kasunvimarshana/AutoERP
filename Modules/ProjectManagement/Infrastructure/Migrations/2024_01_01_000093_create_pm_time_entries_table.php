<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('pm_time_entries', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->index();
            $table->uuid('project_id')->index();
            $table->uuid('task_id')->nullable()->index();
            $table->uuid('user_id')->index();
            $table->decimal('hours', 10, 2);
            $table->text('description')->nullable();
            $table->date('entry_date');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pm_time_entries');
    }
};
