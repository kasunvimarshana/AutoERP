<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organization_unit_legal_profiles', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1);
            $table->foreignId('tenant_id')->constrained('tenants', 'id', indexName: 'ou_legal_profiles_tenant_fk')->restrictOnDelete();
            $table->foreignId('organization_unit_id');
            $table->string('legal_name');
            $table->string('tin', 150)->nullable();
            $table->string('vat_registration_number', 150)->nullable();
            $table->string('svat_registration_number', 150)->nullable();
            $table->string('address_line_1');
            $table->string('address_line_2')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('postal_code', 50)->nullable();
            $table->string('country')->nullable();
            $table->string('phone', 100)->nullable();
            $table->string('email')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'organization_unit_id'], 'ou_legal_profiles_tenant_unit_uk');
            $table->unique(['id', 'tenant_id'], 'ou_legal_profiles_id_tenant_uk');
            $table->foreign(['organization_unit_id', 'tenant_id'], 'ou_legal_profiles_unit_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('organization_units')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organization_unit_legal_profiles');
    }
};
