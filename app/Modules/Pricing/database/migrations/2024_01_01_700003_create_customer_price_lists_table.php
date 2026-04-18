<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_price_lists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained(null, 'id', 'customer_price_lists_customer_id_fk')->cascadeOnDelete();
            $table->foreignId('price_list_id')->constrained(null, 'id', 'customer_price_lists_price_list_id_fk')->cascadeOnDelete();
            $table->unsignedInteger('priority')->default(0);
            $table->timestamps();

            $table->unique(['customer_id', 'price_list_id'], 'customer_price_lists_customer_pricelist_uk');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_price_lists');
    }
};
