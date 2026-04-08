<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_valuation_layers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('variation_id')->nullable();
            $table->unsignedBigInteger('batch_id')->nullable();
            $table->unsignedBigInteger('location_id')->nullable();
            $table->date('layer_date');
            $table->decimal('qty_in', 15, 4)->default(0);
            $table->decimal('qty_remaining', 15, 4)->default(0);
            $table->decimal('unit_cost', 15, 4)->default(0);
            $table->string('currency', 3)->default('USD');
            $table->string('valuation_method', 50);
            $table->string('reference_type', 100)->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->boolean('is_closed')->default(false);
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('batch_id')->references('id')->on('inventory_batches')->nullOnDelete();
            $table->foreign('location_id')->references('id')->on('inventory_locations')->nullOnDelete();

            $table->index('tenant_id');
            $table->index('product_id');
            $table->index('batch_id');
            $table->index('layer_date');
            $table->index('valuation_method');
            $table->index('reference_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_valuation_layers');
    }
};
