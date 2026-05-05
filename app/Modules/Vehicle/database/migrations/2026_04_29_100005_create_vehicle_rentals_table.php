<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_rentals', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('vehicle_id')->constrained('vehicles', 'id')->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('customers', 'id')->nullOnDelete();
            $table->foreignId('assigned_driver_id')->nullable()->constrained('users', 'id')->nullOnDelete();
            $table->string('rental_no', 120);
            $table->enum('rental_status', ['draft', 'reserved', 'active', 'completed', 'cancelled'])->default('draft');
            $table->enum('pricing_model', ['hourly', 'daily', 'weekly', 'monthly', 'kilometer'])->default('daily');
            $table->decimal('base_rate', 20, 6)->default('0.000000');
            $table->decimal('distance_km', 20, 6)->default('0.000000');
            $table->decimal('included_km', 20, 6)->default('0.000000');
            $table->decimal('extra_km_rate', 20, 6)->default('0.000000');
            $table->decimal('subtotal', 20, 6)->default('0.000000');
            $table->decimal('tax_amount', 20, 6)->default('0.000000');
            $table->decimal('grand_total', 20, 6)->default('0.000000');
            $table->timestamp('reserved_from')->nullable();
            $table->timestamp('reserved_until')->nullable();
            $table->timestamp('rented_out_at')->nullable();
            $table->timestamp('returned_at')->nullable();
            $table->decimal('odometer_out', 20, 6)->nullable();
            $table->decimal('odometer_in', 20, 6)->nullable();
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'rental_no'], 'vehicle_rentals_tenant_rental_no_uk');
            $table->index(['tenant_id', 'vehicle_id', 'rental_status'], 'vehicle_rentals_tenant_vehicle_status_idx');
            $table->index(['tenant_id', 'reserved_from', 'reserved_until'], 'vehicle_rentals_tenant_reservation_window_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_rentals');
    }
};
