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
        // Add organizational columns to suppliers table
        if (Schema::hasTable('suppliers') && !Schema::hasColumn('suppliers', 'organization_id')) {
            Schema::table('suppliers', function (Blueprint $table) {
                $table->foreignId('organization_id')
                    ->nullable()
                    ->after('tenant_id')
                    ->constrained('organizations')
                    ->nullOnDelete()
                    ->comment('Organization that manages this supplier');

                $table->index(['tenant_id', 'organization_id']);
            });
        }

        // Add organizational columns to purchase_orders table
        if (Schema::hasTable('purchase_orders') && !Schema::hasColumn('purchase_orders', 'organization_id')) {
            Schema::table('purchase_orders', function (Blueprint $table) {
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
                    ->comment('Delivery location for purchase order');

                $table->index(['tenant_id', 'organization_id']);
                $table->index(['tenant_id', 'location_id']);
                $table->index(['organization_id', 'status']);
            });
        }

        // Add organizational columns to purchase_requisitions table
        if (Schema::hasTable('purchase_requisitions') && !Schema::hasColumn('purchase_requisitions', 'organization_id')) {
            Schema::table('purchase_requisitions', function (Blueprint $table) {
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
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tables = ['suppliers', 'purchase_orders', 'purchase_requisitions'];

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
