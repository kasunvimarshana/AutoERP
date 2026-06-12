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
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units')->nullOnDelete();
            $table->foreignId('item_id')->constrained('items')->cascadeOnDelete();
            $table->foreignId('item_variant_id')->nullable()->constrained('item_variants')->nullOnDelete();
            $table->string('code_type', 40);
            $table->string('code', 120);
            $table->string('party_type')->nullable();
            $table->unsignedBigInteger('party_id')->nullable();
            $table->boolean('is_primary')->default(false);
            $table->timestamps();

            $table->index(['tenant_id', 'organization_unit_id'], 'item_codes_tenant_org_idx');
            $table->index('item_id', 'item_codes_item_idx');
            $table->index('item_variant_id', 'item_codes_variant_idx');
            $table->index(['code_type', 'code'], 'item_codes_type_code_idx');
            $table->index(['party_type', 'party_id'], 'item_codes_party_idx');
            $table->unique(['tenant_id', 'code_type', 'code'], 'item_codes_tenant_type_code_uk');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('item_codes');
    }
};
