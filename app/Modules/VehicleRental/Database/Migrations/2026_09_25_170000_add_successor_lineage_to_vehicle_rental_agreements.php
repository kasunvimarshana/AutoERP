<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const TABLES = [
        'vehicle_rental_customer_agreements' => [
            'identity_unique' => 'vrca_identity_uk',
            'successor_unique' => 'vrca_successor_uk',
            'successor_foreign' => 'vrca_successor_fk',
        ],
        'vehicle_rental_owner_agreements' => [
            'identity_unique' => 'vroa_identity_uk',
            'successor_unique' => 'vroa_successor_uk',
            'successor_foreign' => 'vroa_successor_fk',
        ],
    ];

    public function up(): void
    {
        foreach (self::TABLES as $tableName => $constraints) {
            // The earlier upgrade migration owns the successor column and its original
            // tenant-scoped constraints. This migration only strengthens those constraints;
            // it must not try to create the column a second time on fresh installs.
            Schema::table($tableName, function (Blueprint $table) use ($constraints): void {
                $table->dropForeign($constraints['successor_foreign']);
                $table->dropUnique($constraints['successor_unique']);
            });

            Schema::table($tableName, function (Blueprint $table) use ($tableName, $constraints): void {
                // The scoped identity supports a self-FK that cannot point to another tenant/org.
                $table->unique(
                    ['id', 'tenant_id', 'organization_unit_id'],
                    $constraints['identity_unique'],
                );
                // Agreement IDs are globally unique, so one predecessor ID can have only one
                // direct successor regardless of tenant while the FK also enforces tenant/org scope.
                $table->unique('supersedes_agreement_id', $constraints['successor_unique']);
                $table->foreign(
                    ['supersedes_agreement_id', 'tenant_id', 'organization_unit_id'],
                    $constraints['successor_foreign'],
                )->references(['id', 'tenant_id', 'organization_unit_id'])
                    ->on($tableName)
                    ->restrictOnDelete();
            });
        }
    }

    public function down(): void
    {
        foreach (self::TABLES as $tableName => $constraints) {
            Schema::table($tableName, function (Blueprint $table) use ($constraints): void {
                $table->dropForeign($constraints['successor_foreign']);
                $table->dropUnique($constraints['successor_unique']);
                $table->dropUnique($constraints['identity_unique']);
            });

            // Restore the exact constraints owned by the earlier upgrade migration. Its own
            // down() remains responsible for eventually removing supersedes_agreement_id.
            Schema::table($tableName, function (Blueprint $table) use ($tableName, $constraints): void {
                $table->unique(
                    ['tenant_id', 'supersedes_agreement_id'],
                    $constraints['successor_unique'],
                );
                $table->foreign(
                    ['supersedes_agreement_id', 'tenant_id'],
                    $constraints['successor_foreign'],
                )->references(['id', 'tenant_id'])
                    ->on($tableName)
                    ->restrictOnDelete();
            });
        }
    }
};
