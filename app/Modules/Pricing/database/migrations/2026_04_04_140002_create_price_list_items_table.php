<?php
declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('price_list_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->foreignId('price_list_id')->constrained('price_lists')->cascadeOnDelete();
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('variant_id')->nullable(); // null = all variants
            $table->string('price_type')->default('fixed');       // fixed | percentage
            $table->decimal('value', 15, 4);                      // price OR discount %
            $table->decimal('min_quantity', 15, 4)->default(1);   // tier threshold
            $table->string('currency', 3)->default('USD');
            $table->timestamps();
            $table->softDeletes();
            $table->index('tenant_id');
            $table->index(['price_list_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('price_list_items');
    }
};
