<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::getDriverName();

        if (in_array($driver, ['pgsql', 'sqlite'], true)) {
            DB::statement(
                'CREATE UNIQUE INDEX supplier_addresses_single_default_per_type_uk ON supplier_addresses (tenant_id, supplier_id, type) WHERE is_default = 1'
            );

            DB::statement(
                'CREATE UNIQUE INDEX supplier_contacts_single_primary_per_supplier_uk ON supplier_contacts (tenant_id, supplier_id) WHERE is_primary = 1'
            );

            return;
        }

        if ($driver === 'mysql') {
            Schema::table('supplier_addresses', function (Blueprint $table): void {
                $table->unsignedBigInteger('default_supplier_id')
                    ->nullable()
                    ->storedAs('case when `is_default` = 1 then `supplier_id` else null end');

                $table->string('default_type', 20)
                    ->nullable()
                    ->storedAs('case when `is_default` = 1 then `type` else null end');

                $table->unique(
                    ['tenant_id', 'default_supplier_id', 'default_type'],
                    'supplier_addresses_single_default_per_type_uk'
                );
            });

            Schema::table('supplier_contacts', function (Blueprint $table): void {
                $table->unsignedBigInteger('primary_supplier_id')
                    ->nullable()
                    ->storedAs('case when `is_primary` = 1 then `supplier_id` else null end');

                $table->unique(
                    ['tenant_id', 'primary_supplier_id'],
                    'supplier_contacts_single_primary_per_supplier_uk'
                );
            });
        }
    }

    public function down(): void
    {
        $driver = DB::getDriverName();

        if (in_array($driver, ['pgsql', 'sqlite'], true)) {
            DB::statement('DROP INDEX IF EXISTS supplier_addresses_single_default_per_type_uk');
            DB::statement('DROP INDEX IF EXISTS supplier_contacts_single_primary_per_supplier_uk');

            return;
        }

        if ($driver === 'mysql') {
            Schema::table('supplier_addresses', function (Blueprint $table): void {
                $table->dropUnique('supplier_addresses_single_default_per_type_uk');
                $table->dropColumn(['default_supplier_id', 'default_type']);
            });

            Schema::table('supplier_contacts', function (Blueprint $table): void {
                $table->dropUnique('supplier_contacts_single_primary_per_supplier_uk');
                $table->dropColumn('primary_supplier_id');
            });
        }
    }
};
