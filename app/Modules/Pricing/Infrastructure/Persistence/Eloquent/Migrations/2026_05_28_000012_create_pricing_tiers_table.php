<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pricing_tiers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units', 'id')->nullOnDelete();
            $table->foreignId('price_list_item_id')->nullable()->constrained('price_list_items')->cascadeOnDelete();
            $table->foreignId('pricing_rule_id')->nullable()->constrained('pricing_rules')->cascadeOnDelete();
            $table->foreignId('discount_id')->nullable()->constrained('discounts')->cascadeOnDelete();

            $table->unsignedInteger('sequence')->default(1);
            $table->decimal('min_quantity', 20, 4)->default(1);
            $table->decimal('max_quantity', 20, 4)->nullable();
            $table->foreignId('uom_id')->nullable()->constrained('unit_of_measures')->nullOnDelete();
            $table->foreignId('currency_id')->nullable()->constrained('currencies')->nullOnDelete();
            $table->decimal('price', 20, 4)->nullable();
            $table->string('adjustment_type')->nullable()->comment('percentage, fixed, override');
            $table->decimal('adjustment_value', 20, 4)->nullable();
            $table->unsignedInteger('priority')->default(0);
            $table->date('valid_from')->nullable();
            $table->date('valid_to')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['tenant_id', 'price_list_item_id', 'sequence'], 'pricing_tiers_price_item_sequence_idx');
            $table->index(['tenant_id', 'pricing_rule_id', 'sequence'], 'pricing_tiers_rule_sequence_idx');
            $table->index(['tenant_id', 'discount_id', 'sequence'], 'pricing_tiers_discount_sequence_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pricing_tiers');
    }
};
