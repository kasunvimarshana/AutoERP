<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_category_closures', function (Blueprint $table) {
            $table->unsignedBigInteger('ancestor_id');
            $table->unsignedBigInteger('descendant_id');
            $table->unsignedSmallInteger('depth')->default(0);
            $table->primary(['ancestor_id','descendant_id']);
            $table->foreign('ancestor_id')->references('id')->on('product_categories')->cascadeOnDelete();
            $table->foreign('descendant_id')->references('id')->on('product_categories')->cascadeOnDelete();
        });
    }
    public function down(): void { Schema::dropIfExists('product_category_closures'); }
};
