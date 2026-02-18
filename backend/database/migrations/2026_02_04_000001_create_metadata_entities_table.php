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
        Schema::create('metadata_entities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('name'); // e.g., 'product', 'customer', 'sales_order'
            $table->string('label'); // Display name
            $table->string('label_plural')->nullable(); // Plural display name
            $table->string('table_name')->nullable(); // Database table name
            $table->string('icon')->nullable(); // UI icon
            $table->string('module'); // e.g., 'inventory', 'crm', 'pos'
            $table->text('description')->nullable();
            $table->boolean('is_system')->default(false); // System entities can't be deleted
            $table->boolean('is_active')->default(true);
            $table->boolean('has_workflow')->default(false);
            $table->boolean('has_audit_trail')->default(true);
            $table->boolean('is_tenant_scoped')->default(true);
            $table->json('ui_config')->nullable(); // UI-specific configuration
            $table->json('api_config')->nullable(); // API-specific configuration
            $table->json('permissions')->nullable(); // Entity-level permissions configuration
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'name']);
            $table->index(['module', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('metadata_entities');
    }
};
