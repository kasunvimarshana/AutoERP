<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fleet_rentals', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('org_unit_id')->nullable()->constrained('org_units')->nullOnDelete();
            $table->unsignedBigInteger('row_version')->default(1);
            $table->foreignId('customer_id')->constrained('customers', 'id', 'frent_customer_fk');
            $table->foreignId('vehicle_id')->constrained('fleet_vehicles', 'id', 'frent_vehicle_fk');
            $table->foreignId('driver_id')->nullable()->constrained('fleet_drivers', 'id', 'frent_driver_fk')->nullOnDelete();
            $table->string('rental_number');
            $table->enum('rental_type', ['self_drive', 'with_driver'])->default('self_drive');
            $table->enum('status', ['pending', 'confirmed', 'active', 'completed', 'cancelled'])->default('pending');
            $table->string('pickup_location')->nullable();
            $table->string('return_location')->nullable();
            $table->dateTime('scheduled_start_at');
            $table->dateTime('scheduled_end_at');
            $table->dateTime('actual_start_at')->nullable();
            $table->dateTime('actual_end_at')->nullable();
            $table->decimal('start_odometer', 12, 2)->nullable();
            $table->decimal('end_odometer', 12, 2)->nullable();
            $table->decimal('rate_per_day', 20, 6)->default(0);
            $table->decimal('estimated_days', 10, 4)->default(1);
            $table->decimal('actual_days', 10, 4)->nullable();
            $table->decimal('subtotal', 20, 6)->default(0);
            $table->decimal('discount_amount', 20, 6)->default(0);
            $table->decimal('tax_amount', 20, 6)->default(0);
            $table->decimal('total_amount', 20, 6)->default(0);
            $table->decimal('deposit_amount', 20, 6)->default(0);
            $table->text('notes')->nullable();
            $table->dateTime('cancelled_at')->nullable();
            $table->string('cancellation_reason')->nullable();
            $table->json('metadata')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'org_unit_id', 'rental_number'], 'fleet_rentals_tenant_ou_number_uk');
            $table->index(['tenant_id', 'status'], 'fleet_rentals_tenant_status_idx');
            $table->index(['tenant_id', 'vehicle_id'], 'fleet_rentals_tenant_vehicle_idx');
            $table->index(['tenant_id', 'customer_id'], 'fleet_rentals_tenant_customer_idx');
            $table->index(['tenant_id', 'scheduled_start_at', 'scheduled_end_at'], 'fleet_rentals_tenant_schedule_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fleet_rentals');
    }
};
