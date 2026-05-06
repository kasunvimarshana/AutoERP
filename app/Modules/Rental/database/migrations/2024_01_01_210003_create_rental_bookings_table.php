<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rental_bookings', function (Blueprint $table) {
            $table->id();

            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('org_unit_id')->nullable()->constrained('org_units', 'id')->nullOnDelete();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');

            $table->string('booking_number');
            $table->foreignId('vehicle_id')->constrained('vehicles');
            $table->foreignId('customer_id')->constrained('customers');
            $table->foreignId('contract_id')->nullable()->constrained('rental_contracts')->nullOnDelete();
            $table->enum('booking_type', ['with_driver','without_driver'])->default('without_driver');
            $table->enum('status', ['reserved','active','completed','cancelled','no_show'])->default('reserved');
            $table->dateTime('start_datetime');
            $table->dateTime('end_datetime');
            $table->unsignedBigInteger('start_odometer')->nullable();
            $table->unsignedBigInteger('end_odometer')->nullable();
            $table->foreignId('currency_id')->constrained('currencies');
            $table->decimal('exchange_rate', 20, 10)->default(1);
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['tenant_id','org_unit_id','booking_number'], 'rental_bookings_number_uk');
            $table->index(['vehicle_id','start_datetime','end_datetime'], 'rental_bookings_availability_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rental_bookings');
    }
};
