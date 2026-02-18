<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignId('parent_location_id')->nullable()->constrained('locations')->nullOnDelete();
            
            // Identification
            $table->string('code', 50)->comment('Unique location code');
            $table->json('name')->comment('Translatable location name');
            $table->json('description')->nullable()->comment('Translatable description');
            
            // Classification
            $table->enum('location_type', [
                'headquarters',
                'office',
                'branch',
                'warehouse',
                'factory',
                'retail',
                'distribution_center',
                'transit',
                'virtual',
                'other'
            ])->default('office');
            
            $table->enum('status', ['active', 'inactive', 'under_construction', 'closed'])->default('active');
            
            // Contact Information
            $table->string('email')->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('fax', 50)->nullable();
            $table->string('contact_person')->nullable();
            
            // Address Information
            $table->string('address_line1')->nullable();
            $table->string('address_line2')->nullable();
            $table->string('city', 100)->nullable();
            $table->string('state', 100)->nullable();
            $table->string('postal_code', 20)->nullable();
            $table->string('country', 2)->nullable()->comment('ISO 3166-1 alpha-2');
            
            // Geocoding
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            
            // Operating Information
            $table->json('operating_hours')->nullable()->comment('Operating hours by day');
            $table->string('timezone', 50)->default('UTC');
            
            // Capacity & Size
            $table->decimal('area_sqm', 10, 2)->nullable()->comment('Area in square meters');
            $table->integer('capacity')->nullable()->comment('Storage or personnel capacity');
            
            // Configuration
            $table->json('settings')->nullable()->comment('Location-specific settings');
            $table->json('features')->nullable()->comment('Enabled features');
            $table->json('metadata')->nullable()->comment('Additional metadata');
            
            // Hierarchy tracking
            $table->integer('level')->default(0)->comment('Depth in hierarchy tree');
            $table->string('path')->nullable()->comment('Materialized path');
            
            // Audit
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->unique(['tenant_id', 'code']);
            $table->index(['tenant_id', 'organization_id']);
            $table->index(['tenant_id', 'parent_location_id']);
            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'location_type']);
            $table->index('level');
            $table->index('path');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('locations');
    }
};
