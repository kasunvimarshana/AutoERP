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
                'CREATE UNIQUE INDEX customer_addresses_single_default_per_type_uk ON customer_addresses (tenant_id, customer_id, type) WHERE is_default = 1'
            );

            DB::statement(
                'CREATE UNIQUE INDEX customer_contacts_single_primary_per_customer_uk ON customer_contacts (tenant_id, customer_id) WHERE is_primary = 1'
            );

            return;
        }

        if ($driver === 'mysql') {
            Schema::table('customer_addresses', function (Blueprint $table): void {
                $table->unsignedBigInteger('default_customer_id')
                    ->nullable()
                    ->storedAs('case when `is_default` = 1 then `customer_id` else null end');

                $table->string('default_type', 20)
                    ->nullable()
                    ->storedAs('case when `is_default` = 1 then `type` else null end');

                $table->unique(
                    ['tenant_id', 'default_customer_id', 'default_type'],
                    'customer_addresses_single_default_per_type_uk'
                );
            });

            Schema::table('customer_contacts', function (Blueprint $table): void {
                $table->unsignedBigInteger('primary_customer_id')
                    ->nullable()
                    ->storedAs('case when `is_primary` = 1 then `customer_id` else null end');

                $table->unique(
                    ['tenant_id', 'primary_customer_id'],
                    'customer_contacts_single_primary_per_customer_uk'
                );
            });
        }
    }

    public function down(): void
    {
        $driver = DB::getDriverName();

        if (in_array($driver, ['pgsql', 'sqlite'], true)) {
            DB::statement('DROP INDEX IF EXISTS customer_addresses_single_default_per_type_uk');
            DB::statement('DROP INDEX IF EXISTS customer_contacts_single_primary_per_customer_uk');

            return;
        }

        if ($driver === 'mysql') {
            Schema::table('customer_addresses', function (Blueprint $table): void {
                $table->dropUnique('customer_addresses_single_default_per_type_uk');
                $table->dropColumn(['default_customer_id', 'default_type']);
            });

            Schema::table('customer_contacts', function (Blueprint $table): void {
                $table->dropUnique('customer_contacts_single_primary_per_customer_uk');
                $table->dropColumn('primary_customer_id');
            });
        }
    }
};
