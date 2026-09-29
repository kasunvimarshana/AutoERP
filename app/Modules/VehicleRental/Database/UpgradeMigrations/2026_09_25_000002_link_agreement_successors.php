<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vehicle_rental_customer_agreements', function (Blueprint $table): void {
            $table->unsignedBigInteger('supersedes_agreement_id')->nullable()->after('row_version');
            $table->unique(['tenant_id', 'supersedes_agreement_id'], 'vrca_successor_uk');
            $table->foreign(['supersedes_agreement_id', 'tenant_id'], 'vrca_successor_fk')
                ->references(['id', 'tenant_id'])->on('vehicle_rental_customer_agreements')->restrictOnDelete();
        });
        Schema::table('vehicle_rental_owner_agreements', function (Blueprint $table): void {
            $table->unsignedBigInteger('supersedes_agreement_id')->nullable()->after('row_version');
            $table->unique(['tenant_id', 'supersedes_agreement_id'], 'vroa_successor_uk');
            $table->foreign(['supersedes_agreement_id', 'tenant_id'], 'vroa_successor_fk')
                ->references(['id', 'tenant_id'])->on('vehicle_rental_owner_agreements')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('vehicle_rental_owner_agreements', function (Blueprint $table): void {
            $table->dropForeign(['supersedes_agreement_id', 'tenant_id'])->index('vroa_successor_fk');
            $table->dropUnique('vroa_successor_uk');
            $table->dropColumn('supersedes_agreement_id');
        });
        Schema::table('vehicle_rental_customer_agreements', function (Blueprint $table): void {
            $table->dropForeign(['supersedes_agreement_id', 'tenant_id'])->index('vrca_successor_fk');
            $table->dropUnique('vrca_successor_uk');
            $table->dropColumn('supersedes_agreement_id');
        });
    }
};
