<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('catalog_products', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('name', 255);
            $table->unsignedInteger('price_amount');
            $table->char('price_currency', 3)->default('USD');
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->index('active');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('catalog_products');
    }
};
