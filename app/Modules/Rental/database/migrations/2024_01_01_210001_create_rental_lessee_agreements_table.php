<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rental_lessee_agreements', function (Blueprint $table) {
            $table->id();

            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('org_unit_id')->nullable()->constrained('org_units', 'id')->nullOnDelete();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');

            $table->string('agreement_number');
            // $table->string('party_type');
            // $table->unsignedBigInteger('party_id');
            $table->foreignId('lessee_id')->constrained('customers', 'id')->comment('Vehicle renter');
            $table->foreignId('vehicle_id')->constrained('vehicles', 'id');

            $table->date('agreement_date');
            // $table->dateTime('start_datetime');
            // $table->dateTime('end_datetime')->nullable();
            $table->date('contract_date');
            $table->date('start_date');
            $table->date('end_date')->nullable();

            $table->unsignedBigInteger('start_odometer')->nullable();
            $table->unsignedBigInteger('end_odometer')->nullable();

            // $table->foreignId('currency_id')->nullable()->constrained('currencies', 'id', 'rental_lessor_agreements_currency_id_fk')->nullOnDelete();
            $table->string('agreement_type')->comment('daily, monthly');
            // $table->string('rental_rate_type')->comment('hourly, daily, weekly, monthly, mileage, fixed');
            // $table->decimal('rate_amount', 20, 6)->comment('price per unit (per hour, per day, per km, etc.)');
            $table->decimal('monthly_rate', 20, 6)->nullable();
            $table->decimal('maximum_number_of_km', 10, 2)->nullable()->comment('Maximum number of kilometers allowed');
            $table->decimal('rate_per_km', 20, 6)->nullable();
            $table->decimal('excess_km_rate', 20, 6)->nullable();

            // ─── Driver included? ───
            $table->boolean('driver_included')->default(false);
            $table->decimal('drivers_salary', 20, 6)->nullable();
            $table->decimal('working_hours_per_weekday', 5, 2)->nullable();
            $table->decimal('working_hours_per_saturday', 5, 2)->nullable();
            $table->decimal('working_hours_per_sunday', 5, 2)->nullable();
            $table->decimal('normal_ot_rate_per_hour', 20, 6)->nullable();
            $table->decimal('double_ot_rate_per_hour', 20, 6)->nullable();
            $table->decimal('night_out_rate_per_hour', 20, 6)->nullable();
            $table->decimal('driver_outstation_allowance', 20, 6)->nullable();

            $table->string('status')->default('draft')->comment('draft, active, completed, cancelled');

            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // GL account references
            $table->foreignId('rental_income_id')->nullable()->constrained('accounts', 'id')->nullOnDelete();
            $table->foreignId('excess_km_income_id')->nullable()->constrained('accounts', 'id')->nullOnDelete();
            $table->foreignId('driver_salary_id')->nullable()->constrained('accounts', 'id')->nullOnDelete();
            $table->foreignId('driver_ot_id')->nullable()->constrained('accounts', 'id')->nullOnDelete();
            $table->foreignId('driver_night_out_id')->nullable()->constrained('accounts', 'id')->nullOnDelete();

            $table->unique(['tenant_id', 'org_unit_id', 'agreement_number'], 'lessee_agreements_number_uk');
            $table->index(['tenant_id', 'lessor_id'], 'lessee_agreements_lessee_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rental_lessee_agreements');
    }
};
