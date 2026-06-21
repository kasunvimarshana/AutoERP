<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rental_agreement_rate_versions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1);
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units')->nullOnDelete();
            $table->json('metadata')->nullable();
            $table->foreignId('agreement_id')->constrained('rental_agreements')->cascadeOnDelete();
            $table->unsignedInteger('version_number');
            $table->dateTime('effective_from');
            $table->dateTime('effective_to')->nullable();
            $table->string('driver_mode', 30);
            $table->string('billing_cycle', 30);
            $table->string('billing_basis', 30);
            $table->string('proration_rule', 30)->default('exact_day_count');
            $table->string('excess_km_method', 30)->default('period');
            $table->decimal('included_km', 20, 6)->default('0.000000');
            $table->decimal('included_hours', 20, 6)->default('0.000000');
            $table->unsignedInteger('weekday_included_minutes')->default(0);
            $table->unsignedInteger('saturday_included_minutes')->default(0);
            $table->unsignedInteger('holiday_included_minutes')->default(0);
            $table->foreignId('currency_id')->constrained('currencies')->restrictOnDelete();
            $table->foreignId('tax_group_id')->nullable()->constrained('tax_groups')->nullOnDelete();
            $table->foreignId('withholding_tax_group_id')->nullable()->constrained('tax_groups')->nullOnDelete();
            $table->string('status', 30)->default('draft');
            $table->char('fingerprint', 64);
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->dateTime('approved_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->unique(['agreement_id', 'version_number'], 'rental_rate_versions_agreement_version_uk');
            $table->unique(['tenant_id', 'fingerprint'], 'rental_rate_versions_fingerprint_uk');
            $table->index(['agreement_id', 'effective_from', 'effective_to', 'status'], 'rental_rate_versions_period_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rental_agreement_rate_versions');
    }
};
