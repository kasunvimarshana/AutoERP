<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bom_lines', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('bom_id');
            $table->unsignedBigInteger('component_product_id');
            $table->unsignedBigInteger('component_variant_id')->nullable();
            $table->decimal('quantity', 15, 4);
            $table->text('notes')->nullable();

            $table->foreign('bom_id')->references('id')->on('boms')->cascadeOnDelete();
            $table->index(['bom_id', 'component_product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bom_lines');
    }
};
