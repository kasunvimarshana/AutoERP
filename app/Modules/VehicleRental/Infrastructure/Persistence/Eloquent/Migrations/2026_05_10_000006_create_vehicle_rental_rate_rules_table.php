<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_rental_rate_rules', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1);
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units')->nullOnDelete();
            $table->foreignId('agreement_id')->nullable()->constrained('vehicle_rental_agreements')->cascadeOnDelete();
            $table->foreignId('agreement_rate_id')->nullable()->constrained('vehicle_rental_agreement_rates')->cascadeOnDelete();
            $table->foreignId('threshold_uom_id')->nullable()->constrained('unit_of_measures')->nullOnDelete();

            $table->string('rule_code');
            $table->string('rule_type');
            $table->string('basis_type');
            $table->string('comparator')->default('gt');
            $table->decimal('threshold_quantity', 20, 4)->default(0);
            $table->decimal('rate_value', 20, 4)->default(0);
            $table->decimal('rate_multiplier', 20, 4)->default(1);
            $table->decimal('fixed_amount', 20, 4)->default(0);
            $table->decimal('maximum_charge_amount', 20, 4)->default(0);
            $table->dateTime('applies_from')->nullable();
            $table->dateTime('applies_to')->nullable();
            $table->string('weekday_mask')->nullable();
            $table->unsignedInteger('priority')->default(100);
            $table->boolean('is_cumulative')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->timestamps();

            $table->index(['tenant_id', 'agreement_rate_id'], 'vehicle_rental_rate_rules_rate_idx');
            $table->index(['tenant_id', 'rule_type'], 'vehicle_rental_rate_rules_type_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_rental_rate_rules');
    }
};
