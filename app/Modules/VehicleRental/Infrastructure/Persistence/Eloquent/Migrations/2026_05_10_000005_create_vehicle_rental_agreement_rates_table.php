<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_rental_agreement_rates', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1);
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units')->nullOnDelete();
            $table->foreignId('agreement_id')->constrained('vehicle_rental_agreements')->cascadeOnDelete();
            $table->foreignId('billing_uom_id')->nullable()->constrained('unit_of_measures')->nullOnDelete();
            $table->foreignId('tax_group_id')->nullable()->constrained('tax_groups')->nullOnDelete();

            $table->string('rate_name');
            $table->string('charge_scope')->default('customer')->comment('customer, provider');
            $table->string('rate_model');
            $table->string('usage_basis');
            $table->boolean('is_default')->default(false);
            $table->dateTime('effective_from');
            $table->dateTime('effective_to')->nullable();
            $table->decimal('base_quantity', 20, 4)->default(0);
            $table->decimal('base_rate', 20, 4)->default(0);
            $table->decimal('included_hours', 20, 4)->default(0);
            $table->decimal('included_km', 20, 4)->default(0);
            $table->decimal('extra_hour_rate', 20, 4)->default(0);
            $table->decimal('extra_km_rate', 20, 4)->default(0);
            $table->decimal('overtime_rate', 20, 4)->default(0);
            $table->decimal('night_shift_rate', 20, 4)->default(0);
            $table->decimal('weekend_rate_multiplier', 20, 4)->default(1);
            $table->decimal('holiday_rate_multiplier', 20, 4)->default(1);
            $table->decimal('double_rate_multiplier', 20, 4)->default(2);
            $table->decimal('driver_rate', 20, 4)->default(0);
            $table->decimal('outstation_day_rate', 20, 4)->default(0);
            $table->decimal('outstation_night_rate', 20, 4)->default(0);
            $table->string('fuel_charge_policy')->nullable();
            $table->string('toll_charge_policy')->nullable();
            $table->string('parking_charge_policy')->nullable();
            $table->boolean('is_tax_inclusive')->default(false);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->timestamps();

            $table->index(['tenant_id', 'agreement_id'], 'vehicle_rental_agreement_rates_agreement_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_rental_agreement_rates');
    }
};
