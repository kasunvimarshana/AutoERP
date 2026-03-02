<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('uom_conversions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('product_id');
            // from_uom: the source unit (e.g. "box", "dozen")
            $table->string('from_uom', 50);
            // to_uom: the target unit — always the product's inventory UOM (e.g. "pcs")
            $table->string('to_uom', 50);
            // factor: BCMath-safe decimal — how many to_uom units equal one from_uom unit
            // Example: 1 box = 12 pcs → factor = 12.0000
            $table->decimal('factor', 20, 4);
            $table->timestamps();

            $table->foreign('product_id')
                ->references('id')
                ->on('products')
                ->cascadeOnDelete();

            // A product can have only one conversion per (from_uom, to_uom) pair within a tenant
            $table->unique(['tenant_id', 'product_id', 'from_uom', 'to_uom']);
            $table->index(['tenant_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('uom_conversions');
    }
};
