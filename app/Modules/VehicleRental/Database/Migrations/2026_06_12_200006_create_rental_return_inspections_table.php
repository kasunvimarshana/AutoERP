<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rental_return_inspections', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units')->nullOnDelete();
            $table->foreignId('agreement_id')->constrained('rental_agreements')->cascadeOnDelete();
            $table->foreignId('agreement_vehicle_id')->constrained('rental_agreement_vehicles')->cascadeOnDelete();
            $table->foreignId('vehicle_id')->constrained('vehicles')->restrictOnDelete();
            $table->dateTime('inspected_at');
            $table->decimal('odometer', 20, 6);
            $table->decimal('fuel_level', 20, 6)->nullable();
            $table->decimal('damage_amount', 20, 6)->default('0.000000');
            $table->boolean('is_damage_billable')->default(false);
            $table->text('condition_notes')->nullable();
            $table->text('damage_notes')->nullable();
            $table->json('attachments')->nullable();
            $table->unsignedBigInteger('inspected_by')->nullable();
            $table->timestamps();

            $table->unique('agreement_vehicle_id', 'rental_return_inspections_vehicle_uk');
            $table->index(['tenant_id', 'organization_unit_id'], 'rental_return_inspections_tenant_org_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rental_return_inspections');
    }
};
