<?php declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Create Asset Management Tables
 *
 * This migration creates the core tables for asset management:
 * - assets: Base asset records (vehicles, equipment)
 * - asset_owners: Who owns the assets (company or third-party)
 * - vehicles: Vehicle-specific attributes
 * - asset_documents: Registration, insurance, etc.
 * - asset_depreciation: Depreciation schedule and GL tracking
 */
return new class extends Migration {
    public function up(): void
    {
        // Asset Owner table - tracks who owns assets
        Schema::create('asset_owners', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->string('name');
            $table->enum('owner_type', ['company', 'third_party']);
            $table->string('contact_person')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->text('address')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('country')->nullable();
            $table->string('tax_id')->nullable();
            $table->decimal('commission_percentage', 5, 2)->default(0); // 0-100%
            $table->integer('payment_terms_days')->default(30);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->index('tenant_id');
            $table->index('owner_type');
            $table->index('is_active');
        });

        // Assets table - base for all asset types
        Schema::create('assets', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->uuid('asset_owner_id');
            $table->string('name');
            $table->enum('type', ['vehicle', 'equipment', 'tool', 'machinery']);
            $table->string('serial_number')->nullable()->unique();
            $table->date('purchase_date');
            $table->decimal('acquisition_cost', 20, 6); // DECIMAL for precision
            $table->enum('status', ['active', 'maintenance', 'retired', 'sold', 'damaged'])->default('active');
            $table->enum('depreciation_method', ['straight_line', 'declining_balance', 'units_of_production'])->default('straight_line');
            $table->integer('useful_life_years');
            $table->decimal('salvage_value', 20, 6);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('asset_owner_id')->references('id')->on('asset_owners')->cascadeOnDelete();
            $table->index('tenant_id');
            $table->index('status');
            $table->index('type');
            $table->index('asset_owner_id');
        });

        // Vehicles table - specific vehicle attributes
        Schema::create('vehicles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->uuid('asset_id')->unique(); // One-to-one with assets
            $table->string('vin')->unique(); // Vehicle Identification Number
            $table->string('registration_plate')->unique();
            $table->enum('vehicle_type', ['sedan', 'suv', 'truck', 'van', 'motorcycle', 'pickup', 'minibus']);
            $table->string('make'); // Toyota, Honda, BMW, etc.
            $table->string('model');
            $table->integer('year');
            $table->string('color');
            $table->enum('fuel_type', ['petrol', 'diesel', 'hybrid', 'electric', 'lpg']);
            $table->enum('transmission', ['manual', 'automatic', 'cvt']);
            $table->integer('seating_capacity');
            $table->decimal('fuel_tank_capacity', 8, 2); // Liters
            $table->integer('engine_displacement'); // cc
            $table->integer('current_mileage')->default(0); // km
            $table->uuid('current_location_id')->nullable(); // Warehouse location
            $table->boolean('is_rentable')->default(false);
            $table->boolean('is_serviceable')->default(true);
            $table->enum('status', ['available', 'rented', 'in_maintenance', 'damaged', 'retired'])->default('available');
            $table->string('insurance_policy_number')->nullable();
            $table->date('insurance_expiry_date')->nullable();
            $table->date('last_service_date')->nullable();
            $table->date('next_service_date')->nullable();
            $table->integer('next_service_mileage')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('asset_id')->references('id')->on('assets')->cascadeOnDelete();
            $table->index('tenant_id');
            $table->index('vin');
            $table->index('registration_plate');
            $table->index('status');
            $table->index('vehicle_type');
            $table->index('is_rentable');
            $table->index('current_mileage');
        });

        // Asset Documents table - registration, insurance, etc.
        Schema::create('asset_documents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->uuid('asset_id');
            $table->enum('document_type', ['registration', 'insurance', 'inspection', 'roadworthiness', 'emission', 'tax', 'loan', 'title']);
            $table->string('document_name');
            $table->string('document_number')->unique();
            $table->date('issue_date');
            $table->date('expiry_date')->nullable();
            $table->string('file_path')->nullable();
            $table->string('file_url')->nullable();
            $table->string('issuing_authority')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('asset_id')->references('id')->on('assets')->cascadeOnDelete();
            $table->index('tenant_id');
            $table->index('asset_id');
            $table->index('document_type');
            $table->index('expiry_date');
            $table->index('is_active');
        });

        // Asset Depreciation table - depreciation schedule and GL tracking
        Schema::create('asset_depreciations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->uuid('asset_id');
            $table->integer('year');
            $table->integer('month');
            $table->decimal('original_cost', 20, 6);
            $table->decimal('salvage_value', 20, 6);
            $table->enum('depreciation_method', ['straight_line', 'declining_balance', 'units_of_production']);
            $table->integer('useful_life_years');
            $table->decimal('depreciation_amount', 20, 6);
            $table->decimal('accumulated_depreciation', 20, 6);
            $table->decimal('book_value', 20, 6);
            $table->uuid('journal_entry_id')->nullable(); // Reference to Finance module
            $table->enum('posting_status', ['pending', 'posted', 'reversed'])->default('pending');
            $table->timestamps();
            $table->dateTime('posted_at')->nullable();
            $table->softDeletes();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('asset_id')->references('id')->on('assets')->cascadeOnDelete();
            $table->index('tenant_id');
            $table->index('asset_id');
            $table->index(['year', 'month']);
            $table->index('posting_status');
            $table->unique(['asset_id', 'year', 'month']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_depreciations');
        Schema::dropIfExists('asset_documents');
        Schema::dropIfExists('vehicles');
        Schema::dropIfExists('assets');
        Schema::dropIfExists('asset_owners');
    }
};
