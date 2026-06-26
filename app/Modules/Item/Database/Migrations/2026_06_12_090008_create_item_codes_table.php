<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('item_codes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants', indexName: 'item_codes_tenant_fk')->restrictOnDelete();
            $table->foreignId('organization_unit_id')->nullable();
            $table->foreignId('item_id');
            $table->foreignId('item_variant_id')->nullable();
            $table->string('code_type', 40);
            $table->string('code', 120);
            $table->string('party_type')->nullable();
            $table->unsignedBigInteger('party_id')->nullable();
            $table->boolean('is_primary')->default(false);
            $table->timestamps();

            $table->index(['tenant_id', 'organization_unit_id'], 'item_codes_tenant_org_ix');
            $table->index('item_id', 'item_codes_item_ix');
            $table->index('item_variant_id', 'item_codes_variant_ix');
            $table->index(['code_type', 'code'], 'item_codes_type_code_ix');
            $table->index(['party_type', 'party_id'], 'item_codes_party_ix');
            $table->unique(['tenant_id', 'code_type', 'code'], 'item_codes_tenant_type_code_uk');

            $table->unique(['id', 'tenant_id'], 'item_codes_id_tenant_uk');
            $table->foreign(['organization_unit_id', 'tenant_id'], 'item_codes_organization_unit_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('organization_units')
                ->restrictOnDelete();
            $table->foreign(['item_id', 'tenant_id'], 'item_codes_item_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('items')
                ->cascadeOnDelete();
            $table->foreign(['item_variant_id', 'tenant_id'], 'item_codes_item_variant_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('item_variants')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('item_codes');
    }
};
