<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('item_variants', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete()->comment('Multi-tenant owner reference');
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units', 'id')->nullOnDelete()->comment('Branch or department ownership');
            $table->json('metadata')->nullable()->comment('Extensible custom dynamic data');

            $table->foreignId('item_id')->constrained('items')->cascadeOnDelete();
            $table->string('sku')->nullable();
            $table->string('name');
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->decimal('cost_price', 20, 4)->nullable();
            $table->decimal('sales_price', 20, 4)->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'item_id', 'name'], 'item_variants_item_name_uk');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('item_variants');
    }
};
