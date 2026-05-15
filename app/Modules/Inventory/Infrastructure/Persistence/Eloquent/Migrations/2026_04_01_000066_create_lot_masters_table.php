<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lot_masters', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units', 'id')->nullOnDelete();
            $table->json('metadata')->nullable();

            $table->foreignId('product_id')->constrained('products');
            $table->string('lot_number');
            $table->date('production_date')->nullable();
            $table->date('expiry_date')->nullable();

            $table->timestamps();

            $table->unique(['tenant_id', 'organization_unit_id', 'product_id', 'lot_number'], 'lot_masters_product_lot_number_uk');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lot_masters');
    }
};
