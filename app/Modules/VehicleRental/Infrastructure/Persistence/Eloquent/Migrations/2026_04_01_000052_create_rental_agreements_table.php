<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rental_agreements', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units', 'id')->nullOnDelete();
            $table->json('metadata')->nullable();

            $table->string('agreement_number')->unique('rent_agr_number_uk');
            $table->string('type')->default('lessee')->comment('lessee, lessor');
            $table->foreignId('party_id')->constrained('parties');
            $table->foreignId('vehicle_id')->constrained('vehicles');
            $table->date('agreement_date');
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->string('billing_cycle')->default('daily')->comment('daily, weekly, monthly');
            $table->decimal('daily_rate', 20, 4)->nullable();
            $table->decimal('weekly_rate', 20, 4)->nullable();
            $table->decimal('monthly_rate', 20, 4)->nullable();
            $table->decimal('excess_km_rate', 20, 4)->nullable();
            $table->unsignedInteger('max_km_per_day')->nullable();
            $table->unsignedBigInteger('start_odometer')->nullable();
            $table->unsignedBigInteger('end_odometer')->nullable();
            $table->boolean('driver_included')->default(false);
            $table->decimal('driver_daily_wage', 20, 4)->nullable();
            $table->decimal('driver_ot_rate_normal', 20, 4)->nullable();
            $table->decimal('driver_ot_rate_weekend', 20, 4)->nullable();
            $table->decimal('driver_night_out_allowance', 20, 4)->nullable();
            $table->string('status')->default('draft');
            $table->text('notes')->nullable();
            $table->foreignId('rental_income_account_id')->nullable()->constrained('chart_of_accounts')->nullOnDelete();
            $table->foreignId('rental_expense_account_id')->nullable()->constrained('chart_of_accounts')->nullOnDelete();
            $table->foreignId('excess_km_income_account_id')->nullable()->constrained('chart_of_accounts')->nullOnDelete();
            $table->foreignId('driver_expense_account_id')->nullable()->constrained('chart_of_accounts')->nullOnDelete();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rental_agreements');
    }
};
