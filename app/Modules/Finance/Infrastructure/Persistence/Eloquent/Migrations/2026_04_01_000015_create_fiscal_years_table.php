<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fiscal_years', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units', 'id')->nullOnDelete();
            $table->json('metadata')->nullable();

            $table->string('name')->comment('FY 2025');
            $table->date('start_date')->comment('Start date of the fiscal year');
            $table->date('end_date')->comment('End date of the fiscal year');
            $table->string('status')->default('open')->comment('open, closed, locked');
            $table->boolean('is_current')->default(false)->comment('Indicates if this is the current fiscal year');
            $table->unsignedBigInteger('created_by')->nullable()->comment('User who created the fiscal year');

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'organization_unit_id', 'name'], 'fiscal_years_name_uk');
            $table->index(['tenant_id', 'organization_unit_id', 'is_current'], 'fiscal_years_current_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fiscal_years');
    }
};
