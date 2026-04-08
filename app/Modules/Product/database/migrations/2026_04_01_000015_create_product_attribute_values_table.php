<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_attribute_values', static function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('attribute_id');
            $table->foreign('attribute_id')->references('id')->on('product_attributes')->cascadeOnDelete();
            $table->string('value', 255);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_attribute_values');
    }
};
