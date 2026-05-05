<?php declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Create Driver Management Tables
 *
 * Tables:
 * - drivers: Core driver information
 * - licenses: Driving license tracking
 * - driver_availability: Availability calendar
 * - driver_commissions: Commission earnings tracking
 */
return new class extends Migration {
    public function up(): void
    {
        // Drivers table
        Schema::create('drivers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->uuid('employee_id')->nullable(); // Link to Employee module
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email')->unique();
            $table->string('phone');
            $table->date('date_of_birth');
            $table->enum('driver_type', ['employee', 'contractor'])->default('employee');
            $table->enum('status', ['active', 'on_leave', 'terminated', 'suspended'])->default('active');
            $table->decimal('base_daily_wage', 12, 2);
            $table->decimal('commission_percentage', 5, 2); // 0-100%
            $table->date('active_since');
            $table->date('active_until')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->index('tenant_id');
            $table->index('status');
            $table->index('driver_type');
            $table->index('email');
        });

        // Licenses table
        Schema::create('licenses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->uuid('driver_id');
            $table->string('license_number')->unique();
            $table->string('license_class'); // A, B, C, D, etc.
            $table->date('issue_date');
            $table->date('expiry_date');
            $table->string('issuing_authority')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('driver_id')->references('id')->on('drivers')->cascadeOnDelete();
            $table->index('tenant_id');
            $table->index('driver_id');
            $table->index('expiry_date');
            $table->index('is_active');
        });

        // Driver Availability table
        Schema::create('driver_availability', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->uuid('driver_id');
            $table->date('available_date');
            $table->dateTime('available_from');
            $table->dateTime('available_until');
            $table->boolean('is_available')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('driver_id')->references('id')->on('drivers')->cascadeOnDelete();
            $table->index('tenant_id');
            $table->index('driver_id');
            $table->index(['driver_id', 'available_date']);
            $table->index('available_date');
            $table->index('is_available');
        });

        // Driver Commission table
        Schema::create('driver_commissions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->uuid('driver_id');
            $table->uuid('rental_transaction_id')->nullable(); // Reference to Rental module
            $table->decimal('commission_amount', 20, 6);
            $table->decimal('commission_percentage', 5, 2);
            $table->date('earned_date');
            $table->enum('status', ['pending', 'earned', 'paid', 'reversed'])->default('pending');
            $table->uuid('payment_id')->nullable(); // Reference to Finance module
            $table->timestamps();
            $table->dateTime('paid_at')->nullable();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('driver_id')->references('id')->on('drivers')->cascadeOnDelete();
            $table->index('tenant_id');
            $table->index('driver_id');
            $table->index('status');
            $table->index('earned_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('driver_commissions');
        Schema::dropIfExists('driver_availability');
        Schema::dropIfExists('licenses');
        Schema::dropIfExists('drivers');
    }
};
