<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assets', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('org_unit_id')->nullable()->constrained('org_units', 'id')->nullOnDelete();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');

            // Asset identity
            $table->string('asset_code')->comment('Unique human-readable code within tenant+org scope');
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('usage_mode', ['rent_only', 'service_only', 'dual_use', 'internal_only'])
                ->default('dual_use')
                ->comment('Determines whether asset can be rented, serviced, or both');

            // Classification
            $table->foreignId('product_id')->nullable()->constrained('products', 'id', 'assets_product_id_fk')->nullOnDelete()
                ->comment('Links to product catalog for category/type resolution');
            $table->foreignId('serial_id')->nullable()->constrained('serials', 'id', 'assets_serial_id_fk')->nullOnDelete()
                ->comment('Serial number tracking');
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers', 'id', 'assets_supplier_id_fk')->nullOnDelete()
                ->comment('Primary supplier/vendor who sold or provides the asset');
            $table->foreignId('warehouse_id')->nullable()->constrained('warehouses', 'id', 'assets_warehouse_id_fk')->nullOnDelete()
                ->comment('Home warehouse / storage location');
            $table->foreignId('currency_id')->nullable()->constrained('currencies', 'id', 'assets_currency_id_fk')->nullOnDelete();

            // Vehicle / equipment specific
            $table->string('registration_number')->nullable()->comment('License plate or registration number');
            $table->string('chassis_number')->nullable();
            $table->string('engine_number')->nullable();
            $table->smallInteger('year_of_manufacture')->nullable();
            $table->string('make')->nullable()->comment('Brand, e.g. Toyota');
            $table->string('model')->nullable()->comment('Model name, e.g. Land Cruiser');
            $table->string('color')->nullable();
            $table->string('fuel_type')->nullable()->comment('petrol, diesel, electric, hybrid');

            // Financial
            $table->decimal('purchase_cost', 20, 6)->nullable();
            $table->decimal('book_value', 20, 6)->nullable();
            $table->date('purchase_date')->nullable();

            // Operational tracking
            $table->decimal('current_odometer', 20, 6)->default('0.000000')->comment('Latest odometer reading');
            $table->decimal('engine_hours', 20, 6)->default('0.000000')->comment('Engine hours for machinery');

            // Status
            $table->enum('lifecycle_status', ['active', 'inactive', 'retired', 'sold', 'written_off'])->default('active');
            $table->enum('rental_status', ['available', 'reserved', 'rented', 'overdue', 'maintenance_hold'])->default('available');
            $table->enum('service_status', ['available', 'in_service', 'awaiting_service', 'under_repair'])->default('available');

            // Audit
            $table->foreignId('created_by')->nullable()->constrained('users', 'id', 'assets_created_by_fk')->nullOnDelete();
            $table->json('metadata')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Constraints
            $table->unique(['tenant_id', 'org_unit_id', 'asset_code'], 'assets_tenant_org_code_uk');
            $table->index(['tenant_id', 'usage_mode', 'lifecycle_status'], 'assets_tenant_usage_lifecycle_idx');
            $table->index(['tenant_id', 'rental_status', 'lifecycle_status'], 'assets_tenant_rental_status_idx');
            $table->index(['tenant_id', 'service_status', 'lifecycle_status'], 'assets_tenant_service_status_idx');
            $table->index(['tenant_id', 'supplier_id'], 'assets_tenant_supplier_idx');
        });

        Schema::create('asset_status_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('org_unit_id')->nullable()->constrained('org_units', 'id')->nullOnDelete();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');

            $table->foreignId('asset_id')->constrained('assets', 'id', 'asset_status_events_asset_id_fk')->cascadeOnDelete();
            $table->enum('event_type', ['usage_mode_changed', 'lifecycle_changed', 'rental_status_changed', 'service_status_changed']);
            $table->string('from_status')->nullable();
            $table->string('to_status');
            $table->nullableMorphs('source');
            $table->foreignId('triggered_by')->nullable()->constrained('users', 'id', 'asset_status_events_triggered_by_fk')->nullOnDelete();
            $table->timestamp('event_at');
            $table->text('reason')->nullable();
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();

            $table->index(['tenant_id', 'asset_id', 'event_type', 'event_at'], 'asset_status_events_asset_timeline_idx');
            $table->index(['tenant_id', 'event_at'], 'asset_status_events_tenant_date_idx');
        });

        Schema::create('asset_availability_blocks', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('org_unit_id')->nullable()->constrained('org_units', 'id')->nullOnDelete();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');

            $table->foreignId('asset_id')->constrained('assets', 'id', 'asset_availability_blocks_asset_id_fk')->cascadeOnDelete();
            $table->enum('block_type', ['rental_booking', 'service_job', 'internal_reservation', 'manual_hold']);
            $table->nullableMorphs('source');
            $table->timestamp('starts_at');
            $table->timestamp('ends_at')->nullable()->comment('Null means open-ended (e.g. currently rented)');
            $table->boolean('is_exclusive')->default(true)->comment('True = no other block of any type can overlap');
            $table->enum('status', ['active', 'released', 'expired'])->default('active');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'asset_id', 'status', 'starts_at', 'ends_at'], 'asset_avail_blocks_conflict_check_idx');
            $table->index(['tenant_id', 'block_type', 'status'], 'asset_avail_blocks_type_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_availability_blocks');
        Schema::dropIfExists('asset_status_events');
        Schema::dropIfExists('assets');
    }
};
