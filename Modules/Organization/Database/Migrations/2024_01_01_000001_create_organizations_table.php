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
        Schema::create('organizations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('organizations')->nullOnDelete();
            
            // Identification
            $table->string('code', 50)->comment('Unique organization code');
            $table->json('name')->comment('Translatable organization name');
            $table->string('legal_name')->nullable()->comment('Legal business name');
            $table->string('tax_id', 100)->nullable()->comment('Tax identification number');
            $table->string('registration_number', 100)->nullable()->comment('Business registration number');
            
            // Classification
            $table->enum('organization_type', [
                'headquarters',
                'subsidiary',
                'branch',
                'division',
                'department',
                'other'
            ])->default('branch');
            
            $table->enum('status', ['active', 'inactive', 'suspended', 'closed'])->default('active');
            
            // Contact Information
            $table->string('email')->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('fax', 50)->nullable();
            $table->string('website')->nullable();
            
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
            
            // Configuration
            $table->json('settings')->nullable()->comment('Organization-specific settings');
            $table->json('features')->nullable()->comment('Enabled features');
            $table->json('metadata')->nullable()->comment('Additional metadata');
            
            // Hierarchy tracking
            $table->integer('level')->default(0)->comment('Depth in hierarchy tree');
            $table->string('path')->nullable()->comment('Materialized path (e.g., /1/5/12/)');
            
            // Audit
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->unique(['tenant_id', 'code']);
            $table->index(['tenant_id', 'parent_id']);
            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'organization_type']);
            $table->index('level');
            $table->index('path');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('organizations');
    }
};
