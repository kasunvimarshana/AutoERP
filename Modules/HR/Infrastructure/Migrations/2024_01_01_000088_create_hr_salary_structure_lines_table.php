<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hr_salary_structure_lines', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('structure_id');
            $table->uuid('component_id');
            $table->unsignedSmallInteger('sequence')->default(10);
            $table->decimal('override_amount', 18, 8)->nullable();
            $table->timestamps();

            $table->index('structure_id');
            $table->index('component_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hr_salary_structure_lines');
    }
};
