<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Keep the published migration identity. Columns belong to the earlier upgrade.
    // Columns identify foreign keys on SQLite; explicit index names identify them on MySQL.
    public function up(): void
    {
        Schema::table('vehicle_rental_customer_agreements', function (Blueprint $table): void {
            $table->dropForeign(['supersedes_agreement_id', 'tenant_id'])->index('vrca_successor_fk');
            $table->dropUnique('vrca_successor_uk');
        });
        Schema::table('vehicle_rental_customer_agreements', function (Blueprint $table): void {
            $table->unique(['id', 'tenant_id', 'organization_unit_id'], 'vrca_identity_uk');
            $table->unique('supersedes_agreement_id', 'vrca_successor_uk');
            $table->foreign(['supersedes_agreement_id', 'tenant_id', 'organization_unit_id'], 'vrca_successor_fk')
                ->references(['id', 'tenant_id', 'organization_unit_id'])->on('vehicle_rental_customer_agreements')->restrictOnDelete();
        });

        Schema::table('vehicle_rental_owner_agreements', function (Blueprint $table): void {
            $table->dropForeign(['supersedes_agreement_id', 'tenant_id'])->index('vroa_successor_fk');
            $table->dropUnique('vroa_successor_uk');
        });
        Schema::table('vehicle_rental_owner_agreements', function (Blueprint $table): void {
            $table->unique(['id', 'tenant_id', 'organization_unit_id'], 'vroa_identity_uk');
            $table->unique('supersedes_agreement_id', 'vroa_successor_uk');
            $table->foreign(['supersedes_agreement_id', 'tenant_id', 'organization_unit_id'], 'vroa_successor_fk')
                ->references(['id', 'tenant_id', 'organization_unit_id'])->on('vehicle_rental_owner_agreements')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('vehicle_rental_owner_agreements', function (Blueprint $table): void {
            $table->dropForeign(['supersedes_agreement_id', 'tenant_id', 'organization_unit_id'])->index('vroa_successor_fk');
            $table->dropUnique('vroa_successor_uk');
            $table->dropUnique('vroa_identity_uk');
        });
        Schema::table('vehicle_rental_owner_agreements', function (Blueprint $table): void {
            $table->unique(['tenant_id', 'supersedes_agreement_id'], 'vroa_successor_uk');
            $table->foreign(['supersedes_agreement_id', 'tenant_id'], 'vroa_successor_fk')
                ->references(['id', 'tenant_id'])->on('vehicle_rental_owner_agreements')->restrictOnDelete();
        });

        Schema::table('vehicle_rental_customer_agreements', function (Blueprint $table): void {
            $table->dropForeign(['supersedes_agreement_id', 'tenant_id', 'organization_unit_id'])->index('vrca_successor_fk');
            $table->dropUnique('vrca_successor_uk');
            $table->dropUnique('vrca_identity_uk');
        });
        Schema::table('vehicle_rental_customer_agreements', function (Blueprint $table): void {
            $table->unique(['tenant_id', 'supersedes_agreement_id'], 'vrca_successor_uk');
            $table->foreign(['supersedes_agreement_id', 'tenant_id'], 'vrca_successor_fk')
                ->references(['id', 'tenant_id'])->on('vehicle_rental_customer_agreements')->restrictOnDelete();
        });
    }
};
