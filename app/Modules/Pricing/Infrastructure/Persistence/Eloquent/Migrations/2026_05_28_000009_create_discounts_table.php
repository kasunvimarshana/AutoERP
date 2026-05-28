<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('discounts', function (Blueprint $table) {
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
            $table->string('discount_type')->default('percentage')->comment('percentage, fixed');
            $table->decimal('discount_value', 20, 4)->default(0);
            $table->foreignId('item_id')->nullable()->constrained('items')->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->nullOnDelete();
            $table->foreignId('currency_id')->nullable()->constrained('currencies')->nullOnDelete();
            $table->foreignId('uom_id')->nullable()->constrained('unit_of_measures')->nullOnDelete();
            $table->string('source_type')->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->decimal('min_quantity', 20, 4)->nullable();
            $table->decimal('max_quantity', 20, 4)->nullable();
            $table->unsignedInteger('priority')->default(0);
            $table->boolean('is_stackable')->default(true);
            $table->boolean('is_exclusive')->default(false);
            $table->date('valid_from')->nullable();
            $table->date('valid_to')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['tenant_id', 'code'], 'discounts_code_uk');
            $table->index(['tenant_id', 'priority'], 'discounts_priority_idx');
            $table->index(['tenant_id', 'is_active'], 'discounts_active_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('discounts');
    }
};
