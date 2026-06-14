<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rental_reservations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units')->nullOnDelete();
            $table->string('reservation_number', 100);
            $table->string('direction', 20);
            $table->string('party_type', 20);
            $table->unsignedBigInteger('party_id');
            $table->string('rental_type', 30);
            $table->foreignId('vehicle_id')->nullable()->constrained('vehicles')->restrictOnDelete();
            $table->dateTime('start_at');
            $table->dateTime('expected_end_at');
            $table->foreignId('currency_id')->nullable()->constrained('currencies')->nullOnDelete();
            $table->string('status', 20)->default('draft');
            $table->text('remarks')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'reservation_number'], 'rental_reservations_tenant_number_uk');
            $table->index(['tenant_id', 'organization_unit_id'], 'rental_reservations_tenant_org_idx');
            $table->index(['vehicle_id', 'start_at', 'expected_end_at'], 'rental_reservations_vehicle_period_idx');
            $table->index(['party_type', 'party_id'], 'rental_reservations_party_idx');
            $table->index(['status', 'start_at'], 'rental_reservations_status_start_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rental_reservations');
    }
};
