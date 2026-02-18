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
        // Add organizational columns to accounts table
        if (Schema::hasTable('accounts') && !Schema::hasColumn('accounts', 'organization_id')) {
            Schema::table('accounts', function (Blueprint $table) {
                $table->foreignId('organization_id')
                    ->nullable()
                    ->after('tenant_id')
                    ->constrained('organizations')
                    ->nullOnDelete()
                    ->comment('Organization that owns this account');

                $table->index(['tenant_id', 'organization_id']);
            });
        }

        // Add organizational columns to journal_entries table
        if (Schema::hasTable('journal_entries') && !Schema::hasColumn('journal_entries', 'organization_id')) {
            Schema::table('journal_entries', function (Blueprint $table) {
                $table->foreignId('organization_id')
                    ->nullable()
                    ->after('tenant_id')
                    ->constrained('organizations')
                    ->nullOnDelete();

                $table->index(['tenant_id', 'organization_id']);
                $table->index(['organization_id', 'entry_date']);
            });
        }

        // Add organizational columns to invoices table
        if (Schema::hasTable('invoices') && !Schema::hasColumn('invoices', 'organization_id')) {
            Schema::table('invoices', function (Blueprint $table) {
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
                    ->comment('Location where invoice is issued');

                $table->index(['tenant_id', 'organization_id']);
                $table->index(['organization_id', 'status']);
            });
        }

        // Add organizational columns to payments table
        if (Schema::hasTable('payments') && !Schema::hasColumn('payments', 'organization_id')) {
            Schema::table('payments', function (Blueprint $table) {
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
        $tables = ['accounts', 'journal_entries', 'invoices', 'payments'];

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
