<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_rental_running_chart_lines', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1);
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units')->nullOnDelete();
            $table->foreignId('running_chart_id')->constrained('vehicle_rental_running_charts')->cascadeOnDelete();
            $table->foreignId('rental_vehicle_id')->constrained('vehicle_rental_vehicles')->restrictOnDelete();
            $table->foreignId('driver_id')->nullable()->constrained('employees')->nullOnDelete();

            $table->unsignedInteger('line_number');
            $table->string('agreement_side')->default('lessee')->comment('lessee, lessor');
            $table->date('usage_date');
            $table->string('usage_type')->default('normal');
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->decimal('start_km', 20, 4)->default(0);
            $table->decimal('end_km', 20, 4)->default(0);
            $table->decimal('total_hours', 20, 4)->default(0);
            $table->decimal('total_km', 20, 4)->default(0);
            $table->decimal('allowed_hours', 20, 4)->default(0);
            $table->decimal('extra_hours', 20, 4)->default(0);
            $table->decimal('allowed_km', 20, 4)->default(0);
            $table->decimal('extra_km', 20, 4)->default(0);
            $table->decimal('overtime_hours', 20, 4)->default(0);
            $table->decimal('night_shift_hours', 20, 4)->default(0);
            $table->decimal('weekend_hours', 20, 4)->default(0);
            $table->decimal('holiday_hours', 20, 4)->default(0);
            $table->decimal('double_rate_hours', 20, 4)->default(0);
            $table->unsignedInteger('day_out_count')->default(0);
            $table->unsignedInteger('night_out_count')->default(0);
            $table->decimal('fuel_amount', 20, 4)->default(0);
            $table->decimal('driver_charge_amount', 20, 4)->default(0);
            $table->decimal('mileage_charge_amount', 20, 4)->default(0);
            $table->decimal('toll_amount', 20, 4)->default(0);
            $table->decimal('parking_amount', 20, 4)->default(0);
            $table->decimal('other_expense_amount', 20, 4)->default(0);
            $table->decimal('deduction_amount', 20, 4)->default(0);
            $table->decimal('customer_charge_amount', 20, 4)->default(0);
            $table->decimal('provider_cost_amount', 20, 4)->default(0);
            $table->string('status')->default('draft');
            $table->text('remarks')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->dateTime('approved_at')->nullable();

            $table->timestamps();

            $table->unique(
                ['running_chart_id', 'line_number'],
                'vehicle_rental_running_chart_lines_number_uk',
            );
            $table->index(
                ['tenant_id', 'running_chart_id'],
                'vehicle_rental_running_chart_lines_chart_idx',
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_rental_running_chart_lines');
    }
};
