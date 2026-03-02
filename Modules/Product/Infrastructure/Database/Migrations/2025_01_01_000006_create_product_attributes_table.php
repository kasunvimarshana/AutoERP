<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_attributes', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('product_id');
            $table->string('attribute_key', 100);
            $table->string('attribute_label', 255);
            $table->text('attribute_value');
            $table->string('attribute_type', 20)->default('text');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->foreign('product_id')
                ->references('id')
                ->on('products')
                ->cascadeOnDelete();

            $table->index(['tenant_id', 'product_id']);
            $table->unique(['tenant_id', 'product_id', 'attribute_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_attributes');
    }
};
