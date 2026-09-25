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
            Schema::table($tableName, function (Blueprint $table) use ($tableName, $constraints): void {
                $table->unsignedBigInteger('supersedes_agreement_id')->nullable()->after('organization_unit_id');

                // The scoped identity supports a self-FK that cannot point to another tenant/org.
                $table->unique(
                    ['id', 'tenant_id', 'organization_unit_id'],
                    $constraints['identity_unique'],
                );
                // One agreement revision may have at most one direct successor.
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
                $table->dropColumn('supersedes_agreement_id');
            });
        }
    }
};
