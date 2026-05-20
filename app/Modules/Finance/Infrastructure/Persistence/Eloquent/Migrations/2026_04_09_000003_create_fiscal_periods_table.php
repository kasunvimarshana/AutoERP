<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fiscal_periods', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units', 'id')->nullOnDelete();
            $table->json('metadata')->nullable();

            $table->foreignId('fiscal_year_id')->constrained('fiscal_years')->cascadeOnDelete();
            $table->unsignedInteger('period_number')->comment('1-12 for months, 1-4 for quarters');
            $table->string('name')->comment('"January 2025", "Q1 2025"');
            $table->date('start_date')->comment('Start date of the fiscal period');
            $table->date('end_date')->comment('End date of the fiscal period');
            $table->string('status')->default('open')->comment('open, closed, locked');
            $table->string('period_type')->default('month')->comment('month, quarter, year');
            $table->unsignedBigInteger('created_by')->nullable()->comment('User who created the fiscal period');

            $table->timestamps();

            $table->unique(['tenant_id', 'fiscal_year_id', 'period_number'], 'fiscal_periods_tenant_year_period_uk');
            $table->index(['tenant_id', 'status'], 'fiscal_periods_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fiscal_periods');
    }
};
