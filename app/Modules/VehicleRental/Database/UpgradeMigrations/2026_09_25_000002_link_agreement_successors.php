<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->addSuccessor('vehicle_rental_customer_agreements', 'vrca');
        $this->addSuccessor('vehicle_rental_owner_agreements', 'vroa');
    }

    public function down(): void
    {
        $this->dropSuccessor('vehicle_rental_owner_agreements', 'vroa');
        $this->dropSuccessor('vehicle_rental_customer_agreements', 'vrca');
    }

    private function addSuccessor(string $tableName, string $prefix): void
    {
        Schema::table($tableName, function (Blueprint $table) use ($tableName, $prefix): void {
            $table->unsignedBigInteger('supersedes_agreement_id')->nullable()->after('row_version');
            $table->unique(['tenant_id', 'supersedes_agreement_id'], $prefix.'_successor_uk');
            $table->foreign(['supersedes_agreement_id', 'tenant_id'], $prefix.'_successor_fk')
                ->references(['id', 'tenant_id'])
                ->on($tableName)
                ->restrictOnDelete();
        });
    }

    private function dropSuccessor(string $tableName, string $prefix): void
    {
        Schema::table($tableName, function (Blueprint $table) use ($prefix): void {
            $table->dropForeign($prefix.'_successor_fk');
            $table->dropUnique($prefix.'_successor_uk');
            $table->dropColumn('supersedes_agreement_id');
        });
    }
};
