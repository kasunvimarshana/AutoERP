<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('rental_reservations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->uuid('vehicle_id');
            $table->uuid('customer_id');
            $table->uuid('driver_id')->nullable();
            $table->string('reservation_number')->unique();
            $table->dateTime('start_at');
            $table->dateTime('expected_return_at');
            $table->enum('billing_unit', ['hourly', 'daily', 'distance']);
            $table->decimal('base_rate', 20, 6);
            $table->decimal('estimated_distance', 20, 6)->default(0);
            $table->decimal('estimated_amount', 20, 6);
            $table->enum('status', ['draft', 'confirmed', 'cancelled', 'expired'])->default('draft');
            $table->unsignedInteger('version')->default(1);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->index('tenant_id');
            $table->index(['tenant_id', 'vehicle_id', 'start_at']);
            $table->index(['tenant_id', 'status']);
        });

        Schema::create('rental_agreements', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->uuid('reservation_id');
            $table->string('agreement_number')->unique();
            $table->string('digital_agreement_url')->nullable();
            $table->decimal('security_deposit', 20, 6)->default(0);
            $table->string('currency_code', 3)->default('USD');
            $table->string('fuel_policy');
            $table->string('mileage_policy');
            $table->enum('status', ['draft', 'active', 'completed', 'cancelled'])->default('draft');
            $table->dateTime('signed_at');
            $table->unsignedInteger('version')->default(1);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('reservation_id')->references('id')->on('rental_reservations')->cascadeOnDelete();
            $table->index('tenant_id');
            $table->index(['tenant_id', 'status']);
        });

        Schema::create('rental_transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->uuid('agreement_id');
            $table->dateTime('checked_out_at');
            $table->dateTime('checked_in_at')->nullable();
            $table->unsignedBigInteger('odometer_out');
            $table->unsignedBigInteger('odometer_in')->nullable();
            $table->string('fuel_level_out');
            $table->string('fuel_level_in')->nullable();
            $table->decimal('pickup_latitude', 10, 7)->nullable();
            $table->decimal('pickup_longitude', 10, 7)->nullable();
            $table->decimal('dropoff_latitude', 10, 7)->nullable();
            $table->decimal('dropoff_longitude', 10, 7)->nullable();
            $table->enum('status', ['open', 'closed'])->default('open');
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('agreement_id')->references('id')->on('rental_agreements')->cascadeOnDelete();
            $table->index('tenant_id');
            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'agreement_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rental_transactions');
        Schema::dropIfExists('rental_agreements');
        Schema::dropIfExists('rental_reservations');
    }
};
