<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_rental_running_charts', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1);
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units')->nullOnDelete();
            $table->foreignId('agreement_id')->constrained('vehicle_rental_agreements')->cascadeOnDelete();
            $table->foreignId('rental_vehicle_id')->constrained('vehicle_rental_vehicles')->restrictOnDelete();
            $table->foreignId('driver_id')->nullable()->constrained('employees')->nullOnDelete();

            $table->string('chart_number');
            $table->date('chart_date');
            $table->string('status')->default('draft');
            $table->decimal('total_hours', 20, 4)->default(0);
            $table->decimal('allowed_hours', 20, 4)->default(0);
            $table->decimal('extra_hours', 20, 4)->default(0);
            $table->decimal('total_km', 20, 4)->default(0);
            $table->decimal('allowed_km', 20, 4)->default(0);
            $table->decimal('extra_km', 20, 4)->default(0);
            $table->decimal('overtime_hours', 20, 4)->default(0);
            $table->decimal('night_shift_hours', 20, 4)->default(0);
            $table->decimal('weekend_hours', 20, 4)->default(0);
            $table->decimal('holiday_hours', 20, 4)->default(0);
            $table->decimal('double_rate_hours', 20, 4)->default(0);
            $table->unsignedInteger('day_out_count')->default(0);
            $table->unsignedInteger('night_out_count')->default(0);
            $table->decimal('fuel_total', 20, 4)->default(0);
            $table->decimal('toll_total', 20, 4)->default(0);
            $table->decimal('parking_total', 20, 4)->default(0);
            $table->decimal('other_expense_total', 20, 4)->default(0);
            $table->decimal('customer_bill_total', 20, 4)->default(0);
            $table->decimal('provider_cost_total', 20, 4)->default(0);
            $table->text('remarks')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('submitted_by')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->unsignedBigInteger('invoiced_by')->nullable();
            $table->unsignedBigInteger('cancelled_by')->nullable();
            $table->dateTime('submitted_at')->nullable();
            $table->dateTime('approved_at')->nullable();
            $table->dateTime('invoiced_at')->nullable();
            $table->dateTime('cancelled_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'chart_number'], 'vehicle_rental_running_charts_number_uk');
            $table->index(['tenant_id', 'agreement_id'], 'vehicle_rental_running_charts_agreement_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_rental_running_charts');
    }
};
