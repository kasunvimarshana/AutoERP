<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->string('sku', 100);
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('price', 15, 4)->default(0);
            $table->string('currency', 3)->default('USD');
            $table->string('category', 100)->nullable();
            $table->string('status', 50)->default('active');
            $table->json('attributes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'sku']);
            $table->index(['tenant_id', 'category', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
