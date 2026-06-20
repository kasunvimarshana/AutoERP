<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rental_charge_runs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->restrictOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units')->restrictOnDelete();
            $table->foreignId('billing_period_id')->constrained('rental_billing_periods')->restrictOnDelete();
            $table->foreignId('agreement_id')->constrained('rental_agreements')->restrictOnDelete();
            $table->foreignId('rate_snapshot_id')->constrained('rental_agreement_rate_snapshots')->restrictOnDelete();
            $table->string('agreement_direction', 20);
            $table->string('financial_side', 20);
            $table->string('party_type', 20);
            $table->unsignedBigInteger('party_id');
            $table->dateTime('billing_period_start');
            $table->dateTime('billing_period_end');
            $table->string('billing_cycle_key', 100);
            $table->unsignedInteger('period_sequence');
            $table->unsignedInteger('run_version')->default(1);
            $table->string('calculation_status', 20)->default('draft');
            $table->string('approval_status', 20)->default('pending');
            $table->string('invoice_status', 30)->default('not_invoiced');
            $table->decimal('amount_total', 20, 6)->default('0.000000');
            $table->decimal('tax_total', 20, 6)->default('0.000000');
            $table->decimal('withholding_total', 20, 6)->default('0.000000');
            $table->decimal('grand_total', 20, 6)->default('0.000000');
            $table->string('fingerprint', 64);
            $table->timestamp('calculated_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'fingerprint'], 'rental_charge_runs_fingerprint_uk');
            $table->unique(
                ['agreement_id', 'financial_side', 'rate_snapshot_id', 'billing_period_id', 'run_version'],
                'rental_charge_runs_period_version_uk',
            );
            $table->index(['tenant_id', 'organization_unit_id'], 'rental_charge_runs_tenant_org_idx');
            $table->index(['agreement_id', 'approval_status', 'invoice_status'], 'rental_charge_runs_agreement_status_idx');
            $table->index(['billing_period_start', 'billing_period_end'], 'rental_charge_runs_period_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rental_charge_runs');
    }
};
