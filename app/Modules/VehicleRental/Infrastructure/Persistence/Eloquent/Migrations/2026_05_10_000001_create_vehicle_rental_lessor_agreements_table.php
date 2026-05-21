<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_rental_lessor_agreements', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete()->comment('Multi-tenant owner reference');
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units', 'id')->nullOnDelete()->comment('Branch or department ownership');
            $table->json('metadata')->nullable()->comment('Extensible custom dynamic data');

            $table->string('agreement_number');
            // $table->string('type')->comment('lessee, lessor');
            // $table->string('party_type');
            // $table->unsignedBigInteger('party_id');
            $table->foreignId('lessor_id')->constrained('suppliers', 'id')->comment('Vehicle owner');
            $table->foreignId('vehicle_id')->constrained('vehicles', 'id');
            $table->string('agreement_type')->comment('daily, monthly');

            $table->date('agreement_date')->comment('Date agreement signed');
            // $table->dateTime('start_datetime');
            // $table->dateTime('end_datetime')->nullable();
            $table->date('start_date');
            $table->date('end_date')->nullable();
            // $table->string('billing_cycle')->comment('daily, weekly, monthly');
            $table->decimal('daily_rate', 20, 4)->nullable();
            $table->decimal('weekly_rate', 20, 4)->nullable();
            $table->decimal('monthly_rate', 20, 4)->nullable();
            $table->decimal('rate_per_km', 20, 4)->nullable();
            $table->decimal('excess_km_rate', 20, 4)->nullable();
            $table->decimal('maximum_number_of_km', 20, 4)->nullable()->comment('Maximum number of kilometers allowed');
            $table->unsignedBigInteger('start_odometer')->nullable();
            $table->unsignedBigInteger('end_odometer')->nullable();

            // ─── Driver included? ───
            $table->boolean('driver_included')->default(false);
            // $table->decimal('driver_daily_wage', 20, 4)->nullable();
            // $table->decimal('driver_ot_rate_normal', 20, 4)->nullable();
            // $table->decimal('driver_ot_rate_weekend', 20, 4)->nullable();
            // $table->decimal('driver_night_out_allowance', 20, 4)->nullable();
            $table->decimal('drivers_salary', 20, 4)->nullable();
            $table->decimal('working_hours_per_weekday', 20, 4)->nullable();
            $table->decimal('working_hours_per_saturday', 20, 4)->nullable();
            $table->decimal('working_hours_per_sunday', 20, 4)->nullable();
            $table->decimal('normal_ot_rate_per_hour', 20, 4)->nullable();
            $table->decimal('double_ot_rate_per_hour', 20, 4)->nullable();
            $table->decimal('driver_night_out_allowance', 20, 4)->nullable();
            $table->decimal('driver_outstation_allowance', 20, 4)->nullable();

            $table->string('status')->default('draft')->comment('draft, active, completed, cancelled');

            $table->text('notes')->nullable();

            // GL account references
            $table->foreignId('rental_income_account_id')->nullable()->constrained('accounts', 'id', 'vehicle_rental_lessor_agreements_rental_income_fk')->nullOnDelete();
            $table->foreignId('rental_expense_account_id')->nullable()->constrained('accounts', 'id', 'vehicle_rental_lessor_agreements_rental_expense_fk')->nullOnDelete();
            $table->foreignId('excess_km_income_account_id')->nullable()->constrained('accounts', 'id', 'vehicle_rental_lessor_agreements_excess_km_income_fk')->nullOnDelete();
            $table->foreignId('driver_expense_account_id')->nullable()->constrained('accounts', 'id', 'vehicle_rental_lessor_agreements_driver_expense_fk')->nullOnDelete();

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'agreement_number'], 'vehicle_rental_lessor_agreements_agreement_number_uk');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_rental_lessor_agreements');
    }
};
