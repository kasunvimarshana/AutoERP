<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rental_calculation_runs', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1);
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units')->nullOnDelete();
            $table->json('metadata')->nullable();
            $table->foreignId('billing_period_id')->constrained('rental_billing_periods')->cascadeOnDelete();
            $table->unsignedInteger('run_version');
            $table->foreignId('supersedes_run_id')->nullable()->constrained('rental_calculation_runs')->nullOnDelete();
            $table->foreignId('currency_id')->constrained('currencies')->restrictOnDelete();
            $table->string('calculation_status', 30)->default('draft');
            $table->string('document_status', 30)->default('not_generated');
            $table->decimal('net_total', 20, 6)->default('0.000000');
            $table->decimal('discount_total', 20, 6)->default('0.000000');
            $table->decimal('tax_total', 20, 6)->default('0.000000');
            $table->decimal('withholding_total', 20, 6)->default('0.000000');
            $table->decimal('grand_total', 20, 6)->default('0.000000');
            $table->char('fingerprint', 64);
            $table->unsignedBigInteger('calculated_by')->nullable();
            $table->dateTime('calculated_at')->nullable();
            $table->unsignedBigInteger('submitted_by')->nullable();
            $table->dateTime('submitted_at')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->dateTime('approved_at')->nullable();
            $table->unsignedBigInteger('reversed_by')->nullable();
            $table->dateTime('reversed_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->unique(['billing_period_id', 'run_version'], 'rental_calculation_runs_period_version_uk');
            $table->unique(['tenant_id', 'fingerprint'], 'rental_calculation_runs_fingerprint_uk');
            $table->index(['billing_period_id', 'calculation_status'], 'rental_calculation_runs_period_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rental_calculation_runs');
    }
};
