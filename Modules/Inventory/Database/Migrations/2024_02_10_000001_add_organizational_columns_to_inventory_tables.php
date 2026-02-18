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
        // Update warehouses table to link with organization and location
        if (Schema::hasTable('warehouses') && !Schema::hasColumn('warehouses', 'organization_id')) {
            Schema::table('warehouses', function (Blueprint $table) {
                $table->foreignId('organization_id')
                    ->nullable()
                    ->after('tenant_id')
                    ->constrained('organizations')
                    ->nullOnDelete();

                $table->foreignId('location_id')
                    ->nullable()
                    ->after('organization_id')
                    ->constrained('locations')
                    ->nullOnDelete()
                    ->comment('Link warehouse to organizational location');

                $table->index(['tenant_id', 'organization_id']);
                $table->index(['tenant_id', 'location_id']);
            });
        }

        // Add organizational columns to products table
        if (Schema::hasTable('products') && !Schema::hasColumn('products', 'organization_id')) {
            Schema::table('products', function (Blueprint $table) {
                $table->foreignId('organization_id')
                    ->nullable()
                    ->after('tenant_id')
                    ->constrained('organizations')
                    ->nullOnDelete()
                    ->comment('Organization that manages this product');

                $table->index(['tenant_id', 'organization_id']);
            });
        }

        // Add organizational columns to stock_movements table
        if (Schema::hasTable('stock_movements') && !Schema::hasColumn('stock_movements', 'organization_id')) {
            Schema::table('stock_movements', function (Blueprint $table) {
                $table->foreignId('organization_id')
                    ->nullable()
                    ->after('tenant_id')
                    ->constrained('organizations')
                    ->nullOnDelete();

                $table->foreignId('location_id')
                    ->nullable()
                    ->after('organization_id')
                    ->constrained('locations')
                    ->nullOnDelete();

                $table->index(['tenant_id', 'organization_id']);
                $table->index(['organization_id', 'movement_type']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tables = ['warehouses', 'products', 'stock_movements'];

        foreach ($tables as $table) {
            if (Schema::hasTable($table)) {
                Schema::table($table, function (Blueprint $table) {
                    if (Schema::hasColumn($table->getTable(), 'location_id')) {
                        $table->dropForeign(['location_id']);
                    }
                    if (Schema::hasColumn($table->getTable(), 'organization_id')) {
                        $table->dropForeign(['organization_id']);
                    }
                    $table->dropColumn(['organization_id', 'location_id']);
                });
            }
        }
    }
};
