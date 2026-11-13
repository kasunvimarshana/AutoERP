<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\VehicleRental\Constants\AgreementFields;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_rental_billing_cycle_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->restrictOnDelete();
            $table->foreignId('billing_cycle_id');
            $table->string('item_type', 32);
            $table->string('category', 64);
            $table->string('description', 255);
            $table->decimal('quantity', AgreementFields::DECIMAL_PRECISION, AgreementFields::DECIMAL_SCALE)->default(1);
            $table->decimal('unit_rate', AgreementFields::DECIMAL_PRECISION, AgreementFields::DECIMAL_SCALE)->default(0);
            $table->decimal('amount', AgreementFields::DECIMAL_PRECISION, AgreementFields::DECIMAL_SCALE)->default(0);
            $table->timestamps();

            $table->unique(['id', 'tenant_id'], 'vrbci_tenant_uk');
            $table->index(['tenant_id', 'billing_cycle_id'], 'vrbci_cycle_ix');

            $table->foreign(['billing_cycle_id', 'tenant_id'], 'vrbci_cycle_fk')
                ->references(['id', 'tenant_id'])
                ->on('vehicle_rental_billing_cycles')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_rental_billing_cycle_items');
    }
};
