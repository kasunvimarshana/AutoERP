<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rental_agreement_rate_snapshots', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->restrictOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units')->restrictOnDelete();
            $table->foreignId('agreement_id')->constrained('rental_agreements')->restrictOnDelete();
            $table->decimal('base_rate', 20, 6)->default('0.000000');
            $table->string('rate_unit', 20);
            $table->decimal('allowed_hours', 20, 6)->default('0.000000');
            $table->decimal('allowed_km', 20, 6)->default('0.000000');
            $table->decimal('extra_hour_rate', 20, 6)->default('0.000000');
            $table->decimal('extra_km_rate', 20, 6)->default('0.000000');
            $table->decimal('overtime_rate', 20, 6)->default('0.000000');
            $table->decimal('double_overtime_rate', 20, 6)->default('0.000000');
            $table->decimal('night_shift_rate', 20, 6)->default('0.000000');
            $table->decimal('weekend_rate', 20, 6)->default('0.000000');
            $table->decimal('holiday_rate', 20, 6)->default('0.000000');
            $table->decimal('driver_rate', 20, 6)->default('0.000000');
            $table->decimal('outstation_rate', 20, 6)->default('0.000000');
            $table->decimal('day_out_rate', 20, 6)->default('0.000000');
            $table->decimal('night_out_rate', 20, 6)->default('0.000000');
            $table->decimal('fuel_rate', 20, 6)->default('0.000000');
            $table->decimal('waiting_hour_rate', 20, 6)->default('0.000000');
            $table->foreignId('tax_profile_id')->nullable()->constrained('tax_groups')->nullOnDelete();
            $table->foreignId('currency_id')->nullable()->constrained('currencies')->nullOnDelete();
            $table->timestamps();

            $table->unique('agreement_id', 'rental_rate_snapshots_agreement_uk');
            $table->index(['tenant_id', 'organization_unit_id'], 'rental_rate_snapshots_tenant_org_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rental_agreement_rate_snapshots');
    }
};
