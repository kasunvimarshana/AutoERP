<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fleet_rental_charges', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('rental_id')->constrained('fleet_rentals', 'id', 'frent_charge_rental_fk')->cascadeOnDelete();
            $table->enum('charge_type', ['fuel', 'damage', 'overtime', 'toll', 'other'])->default('other');
            $table->string('description');
            $table->decimal('quantity', 10, 4)->default(1);
            $table->decimal('unit_price', 20, 6)->default(0);
            $table->decimal('amount', 20, 6)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['tenant_id', 'rental_id'], 'fleet_rental_charges_tenant_rental_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fleet_rental_charges');
    }
};
