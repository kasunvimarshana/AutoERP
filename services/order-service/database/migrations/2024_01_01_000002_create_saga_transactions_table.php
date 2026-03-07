<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('saga_transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id')->index();
            $table->string('saga_id', 36)->index();
            $table->enum('step', [
                'CREATE_ORDER',
                'RESERVE_INVENTORY',
                'PROCESS_PAYMENT',
                'SEND_NOTIFICATION',
            ]);
            $table->enum('status', [
                'pending',
                'completed',
                'failed',
                'compensating',
                'compensated',
            ])->default('pending')->index();
            $table->json('payload')->nullable();
            $table->json('result')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->foreign('order_id')
                  ->references('id')
                  ->on('orders')
                  ->cascadeOnDelete();

            $table->index(['saga_id', 'step']);
            $table->index(['order_id', 'step']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('saga_transactions');
    }
};
