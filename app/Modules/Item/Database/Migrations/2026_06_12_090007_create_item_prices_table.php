<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('item_prices', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->restrictOnDelete();
            $table->foreignId('organization_unit_id')->nullable();
            $table->foreignId('item_id');
            $table->foreignId('item_variant_id')->nullable();
            $table->string('price_type', 30);
            $table->foreignId('currency_id')->nullable()->constrained('currencies')->nullOnDelete();
            $table->foreignId('uom_id')->nullable();
            $table->decimal('amount', 20, 6);
            $table->date('effective_from')->nullable();
            $table->date('effective_to')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['tenant_id', 'organization_unit_id'], 'item_prices_tenant_org_idx');
            $table->index('item_id', 'item_prices_item_idx');
            $table->index('item_variant_id', 'item_prices_variant_idx');
            $table->index('price_type', 'item_prices_type_idx');

            $table->unique(['id', 'tenant_id'], 'item_prices_id_tenant_uk');
            $table->foreign(['organization_unit_id', 'tenant_id'], 'item_prices_organization_unit_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('organization_units')
                ->restrictOnDelete();
            $table->foreign(['item_id', 'tenant_id'], 'item_prices_item_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('items')
                ->cascadeOnDelete();
            $table->foreign(['item_variant_id', 'tenant_id'], 'item_prices_item_variant_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('item_variants')
                ->restrictOnDelete();
            $table->foreign(['uom_id', 'tenant_id'], 'item_prices_uom_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('unit_of_measures')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('item_prices');
    }
};
