<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fleet_reservations', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->index();
            $table->uuid('org_unit_id')->index();
            $table->unsignedBigInteger('row_version')->default(1);

            $table->string('reservation_number', 64);
            $table->uuid('vehicle_id');
            $table->uuid('customer_id');
            $table->timestamp('reserved_from');
            $table->timestamp('reserved_to');
            $table->enum('status', ['pending', 'confirmed', 'cancelled', 'fulfilled'])->default('pending');
            $table->decimal('estimated_amount', 20, 6)->default('0.000000');
            $table->string('currency', 3)->default('USD');
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(
                ['tenant_id', 'org_unit_id', 'reservation_number'],
                'fleet_reservations_tenant_ou_number_uk'
            );

            $table->index(['tenant_id', 'status'], 'fleet_reservations_tenant_status_idx');
            $table->index(['tenant_id', 'vehicle_id', 'reserved_from'], 'fleet_reservations_tenant_vehicle_from_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fleet_reservations');
    }
};
