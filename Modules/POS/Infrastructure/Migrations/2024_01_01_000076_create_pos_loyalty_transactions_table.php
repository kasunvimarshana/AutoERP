<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pos_loyalty_transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->index();
            $table->uuid('card_id')->index();
            // accrual | redemption
            $table->string('type', 15);
            // Positive for accrual, negative string for redemption
            $table->bigInteger('points');
            $table->string('reference', 100)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('card_id')
                ->references('id')
                ->on('pos_loyalty_cards')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pos_loyalty_transactions');
    }
};
