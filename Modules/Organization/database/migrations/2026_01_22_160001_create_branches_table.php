<?php

declare(strict_types=1);

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
        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained('organizations')->onDelete('cascade')->comment('Parent organization');
            $table->string('branch_code', 50)->unique()->comment('Unique branch code');
            $table->string('name')->index()->comment('Branch name');
            $table->enum('status', ['active', 'inactive', 'maintenance'])->default('active')->index()->comment('Branch status');

            // Branch management
            $table->string('manager_name')->nullable()->comment('Branch manager name');

            // Contact information
            $table->string('email')->nullable()->index()->comment('Branch email');
            $table->string('phone', 20)->nullable()->comment('Branch phone');

            // Address information
            $table->text('address')->nullable()->comment('Street address');
            $table->string('city')->nullable()->index()->comment('City');
            $table->string('state', 100)->nullable()->index()->comment('State/Province');
            $table->string('postal_code', 20)->nullable()->comment('Postal/ZIP code');
            $table->string('country', 2)->nullable()->index()->comment('Country code (ISO 3166-1 alpha-2)');

            // GPS coordinates for location-based services
            $table->decimal('latitude', 10, 8)->nullable()->comment('GPS latitude');
            $table->decimal('longitude', 11, 8)->nullable()->comment('GPS longitude');

            // Operational details
            $table->json('operating_hours')->nullable()->comment('Operating hours (JSON)');
            $table->json('services_offered')->nullable()->comment('Services offered (JSON array)');
            $table->integer('capacity_vehicles')->nullable()->comment('Maximum vehicle capacity per day');
            $table->integer('bay_count')->nullable()->comment('Number of service bays');

            // Additional metadata
            $table->json('metadata')->nullable()->comment('Additional JSON metadata');

            // Timestamps
            $table->timestamps();
            $table->softDeletes();

            // Indexes for common queries
            $table->index(['organization_id', 'status']);
            $table->index(['city', 'status']);
            $table->index('created_at');
            $table->index(['latitude', 'longitude'], 'branches_gps_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('branches');
    }
};
