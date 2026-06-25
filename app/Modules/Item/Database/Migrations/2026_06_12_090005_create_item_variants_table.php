<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('item_variants', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable();
            $table->foreignId('item_id');
            $table->string('code', 80);
            $table->string('sku', 120)->nullable();
            $table->string('barcode', 120)->nullable();
            $table->string('name');
            $table->json('attributes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'code'], 'item_variants_tenant_code_uk');
            $table->index('item_id', 'item_variants_item_idx');

            $table->unique(['id', 'tenant_id'], 'item_variants_id_tenant_uk');
            $table->foreign(['organization_unit_id', 'tenant_id'], 'item_variants_organization_unit_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('organization_units')
                ->restrictOnDelete();
            $table->foreign(['item_id', 'tenant_id'], 'item_variants_item_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('items')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('item_variants');
    }
};
