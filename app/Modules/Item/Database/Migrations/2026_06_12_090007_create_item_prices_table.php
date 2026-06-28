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
            $table->unsignedBigInteger('row_version')->default(1);
            $table->foreignId('tenant_id')->constrained('tenants', indexName: 'item_prices_tenant_fk')->restrictOnDelete();
            $table->foreignId('organization_unit_id')->nullable();
            $table->foreignId('item_id');
            $table->foreignId('item_variant_id')->nullable();
            $table->string('price_type', 30);
            $table->foreignId('currency_id')->constrained('currencies', indexName: 'item_prices_currency_fk')->restrictOnDelete();
            $table->foreignId('uom_id');
            $table->decimal('amount', 20, 6);
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->char('scope_key', 64);
            $table->uuid('lineage_key');
            $table->unsignedInteger('revision_no');
            $table->foreignId('supersedes_price_id')->nullable();
            $table->dateTime('recorded_from');
            $table->dateTime('recorded_to')->nullable();
            $table->text('correction_reason')->nullable();
            $table->timestamps();

            $table->unique(['id', 'tenant_id'], 'item_prices_id_tenant_uk');
            $table->unique(['tenant_id', 'lineage_key', 'revision_no'], 'item_prices_lineage_revision_uk');
            $table->index(['tenant_id', 'item_id', 'scope_key', 'recorded_to'], 'item_prices_current_scope_ix');
            $table->index(['tenant_id', 'item_id', 'effective_from', 'effective_to'], 'item_prices_effective_period_ix');
            $table->index(['tenant_id', 'supersedes_price_id'], 'item_prices_supersedes_ix');

            $table->foreign(['organization_unit_id', 'tenant_id'], 'item_prices_org_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('organization_units')
                ->restrictOnDelete();
            $table->foreign(['item_id', 'tenant_id'], 'item_prices_item_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('items')
                ->restrictOnDelete();
            $table->foreign(['item_variant_id', 'tenant_id'], 'item_prices_variant_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('item_variants')
                ->restrictOnDelete();
            $table->foreign(['uom_id', 'tenant_id'], 'item_prices_uom_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('unit_of_measures')
                ->restrictOnDelete();
            $table->foreign(['supersedes_price_id', 'tenant_id'], 'item_prices_supersedes_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('item_prices')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('item_prices');
    }
};
