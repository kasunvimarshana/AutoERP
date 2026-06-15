<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rental_charge_calculations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->restrictOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units')->restrictOnDelete();
            $table->foreignId('billing_period_id')->constrained('rental_billing_periods')->restrictOnDelete();
            $table->foreignId('charge_run_id')->constrained('rental_charge_runs')->restrictOnDelete();
            $table->foreignId('agreement_id')->constrained('rental_agreements')->restrictOnDelete();
            $table->foreignId('agreement_vehicle_id')->nullable()->constrained('rental_agreement_vehicles')->restrictOnDelete();
            $table->foreignId('usage_log_id')->nullable()->constrained('rental_usage_logs')->restrictOnDelete();
            $table->unsignedBigInteger('usage_context_id')->nullable();
            $table->foreignId('rate_snapshot_id')->nullable()->constrained('rental_agreement_rate_snapshots')->restrictOnDelete();
            $table->string('agreement_direction', 20);
            $table->string('financial_side', 20);
            $table->string('party_type', 20);
            $table->unsignedBigInteger('party_id');
            $table->dateTime('billing_period_start');
            $table->dateTime('billing_period_end');
            $table->string('billing_cycle_key', 100);
            $table->unsignedInteger('period_sequence');
            $table->string('source_type', 50);
            $table->unsignedBigInteger('source_id');
            $table->string('calculation_type', 30);
            $table->decimal('measured_quantity', 20, 6);
            $table->decimal('allowed_quantity', 20, 6)->default('0.000000');
            $table->decimal('chargeable_quantity', 20, 6);
            $table->string('unit', 20)->nullable();
            $table->decimal('rate', 20, 6);
            $table->decimal('multiplier', 20, 6)->default('1.000000');
            $table->decimal('amount', 20, 6);
            $table->foreignId('tax_group_id')->nullable()->constrained('tax_groups')->nullOnDelete();
            $table->decimal('tax_amount', 20, 6)->default('0.000000');
            $table->decimal('withholding_amount', 20, 6)->default('0.000000');
            $table->decimal('total_amount', 20, 6)->default('0.000000');
            $table->string('applied_rule', 100);
            $table->unsignedInteger('calculation_version')->default(1);
            $table->unsignedBigInteger('supersedes_calculation_id')->nullable();
            $table->string('status', 20)->default('draft');
            $table->string('fingerprint', 64);
            $table->text('description')->nullable();
            $table->timestamps();

            $table->unique(
                ['charge_run_id', 'source_type', 'source_id', 'calculation_type'],
                'rental_charge_calculations_source_version_uk',
            );
            $table->unique(['tenant_id', 'fingerprint'], 'rental_charge_calculations_fingerprint_uk');
            $table->index(['tenant_id', 'organization_unit_id'], 'rental_charge_calculations_tenant_org_idx');
            $table->index(['billing_period_id', 'financial_side'], 'rental_charge_calculations_period_side_idx');
            $table->index(['charge_run_id', 'status'], 'rental_charge_calculations_run_status_idx');
            $table->index(['usage_log_id', 'financial_side'], 'rental_charge_calculations_usage_side_idx');
            $table->foreign('supersedes_calculation_id', 'rental_charge_calculations_supersedes_fk')
                ->references('id')
                ->on('rental_charge_calculations')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rental_charge_calculations');
    }
};
