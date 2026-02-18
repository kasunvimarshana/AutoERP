<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Template migration for adding organizational structure columns to existing tables.
 * 
 * Usage:
 * 1. Copy this file to your module's migrations folder
 * 2. Rename appropriately (e.g., add_organizational_columns_to_customers_table.php)
 * 3. Update $tableName variable
 * 4. Run: php artisan migrate
 */
return new class extends Migration
{
    /**
     * The name of the table to add organizational columns to.
     */
    private string $tableName = 'your_table_name_here';

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasColumn($this->tableName, 'organization_id')) {
            Schema::table($this->tableName, function (Blueprint $table) {
                // Add organizational columns after tenant_id if exists, otherwise at beginning
                $afterColumn = Schema::hasColumn($this->tableName, 'tenant_id') ? 'tenant_id' : null;

                if ($afterColumn) {
                    $table->foreignId('organization_id')
                        ->nullable()
                        ->after($afterColumn)
                        ->constrained('organizations')
                        ->nullOnDelete();
                } else {
                    $table->foreignId('organization_id')
                        ->nullable()
                        ->constrained('organizations')
                        ->nullOnDelete();
                }

                $table->foreignId('location_id')
                    ->nullable()
                    ->after('organization_id')
                    ->constrained('locations')
                    ->nullOnDelete();

                // Add indexes for query performance
                $table->index(['tenant_id', 'organization_id'], 'idx_tenant_org');
                $table->index(['tenant_id', 'location_id'], 'idx_tenant_loc');
                $table->index(['organization_id', 'location_id'], 'idx_org_loc');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table($this->tableName, function (Blueprint $table) {
            // Drop indexes first
            $table->dropIndex('idx_tenant_org');
            $table->dropIndex('idx_tenant_loc');
            $table->dropIndex('idx_org_loc');

            // Drop foreign key constraints
            $table->dropForeign(['location_id']);
            $table->dropForeign(['organization_id']);

            // Drop columns
            $table->dropColumn(['organization_id', 'location_id']);
        });
    }
};
