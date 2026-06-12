<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const WITH_ORGANIZATION_UNIT = [
        'organization_unit',
        'department',
        'project',
        'cost_center',
        'branch',
        'customer',
        'supplier',
        'employee',
        'vehicle',
        'custom',
    ];

    private const WITHOUT_ORGANIZATION_UNIT = [
        'department',
        'project',
        'cost_center',
        'branch',
        'customer',
        'supplier',
        'employee',
        'vehicle',
        'custom',
    ];

    public function up(): void
    {
        $this->alterDimensionType(self::WITH_ORGANIZATION_UNIT);
    }

    public function down(): void
    {
        $this->alterDimensionType(self::WITHOUT_ORGANIZATION_UNIT);
    }

    /**
     * SQLite treats Laravel enum columns as text in this project, so no schema
     * rewrite is needed there. MySQL/MariaDB require an explicit enum alter.
     */
    private function alterDimensionType(array $values): void
    {
        if (! Schema::hasTable('finance_dimensions')) {
            return;
        }

        $driver = Schema::getConnection()->getDriverName();

        if (! in_array($driver, ['mysql', 'mariadb'], true)) {
            return;
        }

        $quoted = implode(',', array_map(
            static fn (string $value): string => "'" . str_replace("'", "''", $value) . "'",
            $values,
        ));

        DB::statement("ALTER TABLE finance_dimensions MODIFY dimension_type ENUM({$quoted}) NOT NULL");
    }
};
