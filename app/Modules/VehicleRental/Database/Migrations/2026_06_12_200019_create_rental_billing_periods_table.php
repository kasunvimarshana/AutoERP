<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rental_billing_periods', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1);
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units')->nullOnDelete();
            $table->json('metadata')->nullable();
            $table->foreignId('agreement_id')->constrained('rental_agreements')->cascadeOnDelete();
            $table->string('financial_side', 20);
            $table->foreignId('rate_version_id')->constrained('rental_agreement_rate_versions')->restrictOnDelete();
            $table->dateTime('period_start');
            $table->dateTime('period_end');
            $table->string('billing_cycle_key', 100);
            $table->unsignedInteger('period_sequence');
            $table->string('status', 30)->default('open');
            $table->boolean('is_final')->default(false);
            $table->char('fingerprint', 64);
            $table->unsignedBigInteger('closed_by')->nullable();
            $table->dateTime('closed_at')->nullable();
            $table->unsignedBigInteger('reopened_by')->nullable();
            $table->dateTime('reopened_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'fingerprint'], 'rental_billing_periods_fingerprint_uk');
            $table->unique(['agreement_id', 'financial_side', 'rate_version_id', 'period_start', 'period_end'], 'rental_billing_periods_period_uk');
            $table->index(['agreement_id', 'financial_side', 'status', 'period_start'], 'rental_billing_periods_agreement_side_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rental_billing_periods');
    }
};
