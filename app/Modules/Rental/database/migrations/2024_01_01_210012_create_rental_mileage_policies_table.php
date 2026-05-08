<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rental_mileage_policies', function (Blueprint $table) {
            $table->id();

            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('org_unit_id')->nullable()->constrained('org_units', 'id')->nullOnDelete();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');

            $table->string('name');
            $table->string('applicable_to');
            $table->string('booking_type')->nullable();
            $table->string('vehicle_category')->nullable();
            $table->string('customer_group')->nullable();
            $table->decimal('daily_mileage_allowance_km', 10, 2)->nullable();
            $table->decimal('total_mileage_allowance_km', 10, 2)->nullable();
            $table->decimal('excess_rate_per_km', 20, 6);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rental_mileage_policies');
    }
};
