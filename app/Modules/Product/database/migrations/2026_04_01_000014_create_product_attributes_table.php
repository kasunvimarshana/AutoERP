<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_attributes', static function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->foreign('product_id')->references('id')->on('products')->cascadeOnDelete();
            $table->string('name', 255);
            $table->timestamps();

            $table->index(['product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_attributes');
    }
};
