<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('price_list_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete()->comment('Multi-tenant owner reference');
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units', 'id')->nullOnDelete()->comment('Branch or department ownership');
            $table->json('metadata')->nullable()->comment('Extensible custom dynamic data');

            $table->foreignId('price_list_id')->constrained('price_lists', 'id')->cascadeOnDelete();
            $table->foreignId('item_id')->constrained('items', 'id')->cascadeOnDelete();
            $table->foreignId('variant_id')->nullable()->constrained('item_variants', 'id')->nullOnDelete();
            $table->foreignId('uom_id')->constrained('unit_of_measures', 'id', 'price_list_items_uom_id_fk');
            $table->foreignId('currency_id')->nullable()->constrained('currencies')->nullOnDelete();
            $table->string('party_type')->nullable()->comment('customer, supplier, or other generic party type');
            $table->unsignedBigInteger('party_id')->nullable()->comment('Optional party reference for targeted prices');
            $table->string('source_type')->nullable()->comment('Upstream module or source type');
            $table->unsignedBigInteger('source_id')->nullable()->comment('Upstream module or source reference');
            $table->decimal('min_quantity', 20, 4)->default(1);
            $table->decimal('max_quantity', 20, 4)->nullable();
            $table->decimal('price', 20, 4);
            $table->string('discount_type')->default('percentage')->comment('percentage, fixed');
            $table->decimal('discount_value', 20, 4)->default(0);
            $table->string('markup_type')->nullable()->comment('percentage, fixed');
            $table->decimal('markup_value', 20, 4)->default(0);
            $table->boolean('is_tax_inclusive')->default(false);
            $table->unsignedInteger('priority')->default(0);
            $table->date('valid_from')->nullable();
            $table->date('valid_to')->nullable();
            $table->boolean('is_promotional')->default(false);
            $table->boolean('is_active')->default(true);
            $table->string('reference')->nullable();

            $table->timestamps();

            $table->unique([
                'tenant_id',
                'price_list_id',
                'item_id',
                'variant_id',
                'uom_id',
                'currency_id',
                'party_type',
                'party_id',
                'source_type',
                'source_id',
                'min_quantity',
                'max_quantity',
            ], 'price_list_items_unique_uk');
            $table->index(['tenant_id', 'price_list_id'], 'price_list_items_price_list_idx');
            $table->index(['tenant_id', 'item_id'], 'price_list_items_item_idx');
            $table->index(['tenant_id', 'currency_id'], 'price_list_items_currency_idx');
            $table->index(['tenant_id', 'uom_id'], 'price_list_items_uom_idx');
            $table->index(['tenant_id', 'party_type', 'party_id'], 'price_list_items_party_idx');
            $table->index(['tenant_id', 'source_type', 'source_id'], 'price_list_items_source_idx');
            $table->index(['tenant_id', 'is_active'], 'price_list_items_active_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('price_list_items');
    }
};
