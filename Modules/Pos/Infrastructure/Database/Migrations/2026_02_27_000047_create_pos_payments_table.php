<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pos_payments', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('pos_transaction_id');
            $table->string('payment_method')->comment('cash/card/voucher/loyalty_points/gift_card');
            $table->decimal('amount', 20, 4);
            $table->string('reference')->nullable();
            $table->timestamps();

            $table->foreign('pos_transaction_id')->references('id')->on('pos_transactions')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pos_payments');
    }
};
