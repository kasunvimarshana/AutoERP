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
        // Add organizational columns to customers table
        if (Schema::hasTable('customers') && !Schema::hasColumn('customers', 'organization_id')) {
            Schema::table('customers', function (Blueprint $table) {
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
                    ->comment('Primary location for customer interactions');

                $table->index(['tenant_id', 'organization_id']);
                $table->index(['tenant_id', 'location_id']);
            });
        }

        // Add organizational columns to sales_orders table
        if (Schema::hasTable('sales_orders') && !Schema::hasColumn('sales_orders', 'organization_id')) {
            Schema::table('sales_orders', function (Blueprint $table) {
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
                    ->comment('Location where order is processed');

                $table->index(['tenant_id', 'organization_id']);
                $table->index(['tenant_id', 'location_id']);
                $table->index(['organization_id', 'status']);
            });
        }

        // Add organizational columns to leads table
        if (Schema::hasTable('leads') && !Schema::hasColumn('leads', 'organization_id')) {
            Schema::table('leads', function (Blueprint $table) {
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

        // Add organizational columns to opportunities table
        if (Schema::hasTable('opportunities') && !Schema::hasColumn('opportunities', 'organization_id')) {
            Schema::table('opportunities', function (Blueprint $table) {
                $table->foreignId('organization_id')
                    ->nullable()
                    ->after('tenant_id')
                    ->constrained('organizations')
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
        $tables = ['customers', 'sales_orders', 'leads', 'opportunities'];

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
