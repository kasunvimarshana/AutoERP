<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hr_salary_structure_lines', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units', 'id')->nullOnDelete();
            $table->json('metadata')->nullable();

            $table->foreignId('salary_structure_id')->constrained('hr_salary_structures')->cascadeOnDelete();
            $table->foreignId('salary_component_id')->constrained('hr_salary_components');
            $table->string('calculation_type', 20)->default('fixed');
            $table->decimal('value', 20, 4);
            $table->unsignedInteger('sequence')->default(0);

            $table->timestamps();

            $table->unique(['tenant_id', 'organization_unit_id', 'salary_structure_id', 'salary_component_id'], 'hr_salary_structure_lines_structure_component_uk');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hr_salary_structure_lines');
    }
};
