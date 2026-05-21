<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('valuation_configs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete()->comment('Multi-tenant owner reference');
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units', 'id')->nullOnDelete()->comment('Branch or department ownership');
            $table->json('metadata')->nullable()->comment('Extensible custom dynamic data');

            $table->unsignedBigInteger('warehouse_id')->nullable();
            $table->foreign('warehouse_id')->references('id')->on('warehouses')->nullOnDelete();
            $table->foreignId('item_id')->nullable()->constrained('items')->nullOnDelete();
            // Optional movement-type narrowing
            $table->string('transaction_type')->nullable();
            // Strategy configuration
            $table->string('valuation_method')->nullable()->comment('fifo, lifo, fefo, weighted_average, standard, specific');
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->unique(
                ['tenant_id', 'item_id', 'warehouse_id', 'transaction_type'],
                'valuation_configs_item_warehouse_transaction_uk'
            );
            $table->index(['tenant_id', 'is_active'], 'valuation_configs_active_idx');
            $table->index(['tenant_id', 'item_id'], 'valuation_configs_item_fk');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('valuation_configs');
    }
};
