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
            $table->foreignId('tenant_id')->constrained('tenants')->restrictOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units')->restrictOnDelete();
            $table->foreignId('agreement_id')->constrained('rental_agreements')->restrictOnDelete();
            $table->foreignId('rate_snapshot_id')->constrained('rental_agreement_rate_snapshots')->restrictOnDelete();
            $table->string('agreement_direction', 20);
            $table->string('financial_side', 20);
            $table->string('party_type', 20);
            $table->unsignedBigInteger('party_id');
            $table->dateTime('period_start');
            $table->dateTime('period_end');
            $table->string('billing_cycle_key', 100);
            $table->unsignedInteger('period_sequence');
            $table->string('billing_cycle', 30);
            $table->string('billing_basis', 30);
            $table->string('proration_rule', 30);
            $table->string('status', 20)->default('closed');
            $table->boolean('is_final')->default(false);
            $table->timestamp('closed_at')->nullable();
            $table->unsignedBigInteger('closed_by')->nullable();
            $table->string('fingerprint', 64);
            $table->timestamps();

            $table->unique(['tenant_id', 'fingerprint'], 'rental_billing_periods_fingerprint_uk');
            $table->unique(
                ['agreement_id', 'financial_side', 'rate_snapshot_id', 'period_start', 'period_end'],
                'rental_billing_periods_period_uk',
            );
            $table->index(['tenant_id', 'organization_unit_id'], 'rental_billing_periods_tenant_org_idx');
            $table->index(['agreement_id', 'period_start', 'period_end'], 'rental_billing_periods_agreement_period_idx');
            $table->index(['financial_side', 'status'], 'rental_billing_periods_side_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rental_billing_periods');
    }
};
