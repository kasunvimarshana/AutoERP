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
        // Add organizational columns to departments table
        if (Schema::hasTable('departments') && !Schema::hasColumn('departments', 'organization_id')) {
            Schema::table('departments', function (Blueprint $table) {
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
                $table->index(['tenant_id', 'location_id']);
            });
        }

        // Add organizational columns to employees table
        if (Schema::hasTable('employees') && !Schema::hasColumn('employees', 'organization_id')) {
            Schema::table('employees', function (Blueprint $table) {
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
                $table->index(['tenant_id', 'location_id']);
            });
        }

        // Add organizational columns to positions table
        if (Schema::hasTable('positions') && !Schema::hasColumn('positions', 'organization_id')) {
            Schema::table('positions', function (Blueprint $table) {
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
        if (Schema::hasTable('departments')) {
            Schema::table('departments', function (Blueprint $table) {
                $table->dropForeign(['location_id']);
                $table->dropForeign(['organization_id']);
                $table->dropColumn(['organization_id', 'location_id']);
            });
        }

        if (Schema::hasTable('employees')) {
            Schema::table('employees', function (Blueprint $table) {
                $table->dropForeign(['location_id']);
                $table->dropForeign(['organization_id']);
                $table->dropColumn(['organization_id', 'location_id']);
            });
        }

        if (Schema::hasTable('positions')) {
            Schema::table('positions', function (Blueprint $table) {
                $table->dropForeign(['organization_id']);
                $table->dropColumn('organization_id');
            });
        }
    }
};
