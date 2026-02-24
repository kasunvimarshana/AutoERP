<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('budget_lines', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->index();
            $table->uuid('budget_id')->index();
            $table->string('category');
            $table->text('description')->nullable();
            $table->decimal('planned_amount', 18, 8)->default('0.00000000');
            $table->decimal('actual_amount', 18, 8)->default('0.00000000');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('budget_lines');
    }
};
