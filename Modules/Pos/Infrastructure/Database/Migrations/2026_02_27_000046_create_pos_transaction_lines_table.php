<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pos_transaction_lines', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('pos_transaction_id');
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('uom_id');
            $table->decimal('quantity', 20, 4);
            $table->decimal('unit_price', 20, 4);
            $table->decimal('discount_amount', 20, 4)->default('0.0000');
            $table->decimal('line_total', 20, 4);
            $table->timestamps();

            $table->foreign('pos_transaction_id')->references('id')->on('pos_transactions')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pos_transaction_lines');
    }
};
