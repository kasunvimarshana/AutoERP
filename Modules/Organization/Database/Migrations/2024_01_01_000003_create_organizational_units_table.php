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
        Schema::create('organizational_units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignId('location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->foreignId('parent_unit_id')->nullable()->constrained('organizational_units')->nullOnDelete();
            
            // Identification
            $table->string('code', 50)->comment('Unique unit code');
            $table->json('name')->comment('Translatable unit name');
            $table->json('description')->nullable()->comment('Translatable description');
            
            // Classification
            $table->enum('unit_type', [
                'division',
                'department',
                'team',
                'group',
                'project',
                'other'
            ])->default('department');
            
            $table->enum('status', ['active', 'inactive', 'suspended', 'closed'])->default('active');
            
            // Management
            $table->foreignId('manager_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('email')->nullable();
            $table->string('phone', 50)->nullable();
            
            // Configuration
            $table->json('settings')->nullable()->comment('Unit-specific settings');
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
            $table->index(['tenant_id', 'location_id']);
            $table->index(['tenant_id', 'parent_unit_id']);
            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'unit_type']);
            $table->index('manager_id');
            $table->index('level');
            $table->index('path');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('organizational_units');
    }
};
