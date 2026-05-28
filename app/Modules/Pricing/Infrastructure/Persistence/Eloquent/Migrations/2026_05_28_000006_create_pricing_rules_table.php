<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pricing_rules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')
                ->nullable()
                ->constrained('organization_units', 'id')
                ->nullOnDelete();
            $table->json('metadata')->nullable();

            $table->string('code')->nullable();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('applies_to_type')->default('generic');
            $table->string('source_type')->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->foreignId('item_id')->nullable()->constrained('items')->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->nullOnDelete();
            $table->foreignId('currency_id')->nullable()->constrained('currencies')->nullOnDelete();
            $table->foreignId('uom_id')->nullable()->constrained('unit_of_measures')->nullOnDelete();
            $table->decimal('min_quantity', 20, 4)->nullable();
            $table->decimal('max_quantity', 20, 4)->nullable();
            $table->unsignedInteger('priority')->default(0);
            $table->string('action_type')->default('adjust_price');
            $table->decimal('action_value', 20, 4)->nullable();
            $table->boolean('is_stackable')->default(true);
            $table->boolean('is_exclusive')->default(false);
            $table->date('valid_from')->nullable();
            $table->date('valid_to')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['tenant_id', 'code'], 'pricing_rules_code_uk');
            $table->index(['tenant_id', 'applies_to_type'], 'pricing_rules_applies_to_idx');
            $table->index(['tenant_id', 'priority'], 'pricing_rules_priority_idx');
            $table->index(['tenant_id', 'is_active'], 'pricing_rules_active_idx');
            $table->index(['tenant_id', 'source_type', 'source_id'], 'pricing_rules_source_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pricing_rules');
    }
};
