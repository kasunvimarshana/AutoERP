<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('price_rules', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->index();
            $table->uuid('price_list_id')->index();
            $table->foreign('price_list_id')->references('id')->on('price_lists');
            $table->uuid('product_id')->nullable()->index();
            $table->uuid('category_id')->nullable()->index();
            $table->uuid('variant_id')->nullable()->index();
            $table->decimal('min_qty', 8, 2)->default(1);
            $table->decimal('price', 15, 4);
            $table->decimal('discount_percent', 5, 2)->default(0);
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('price_rules');
    }
};
