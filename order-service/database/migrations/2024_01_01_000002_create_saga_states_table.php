<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('saga_states', function (Blueprint $table) {
            $table->id();
            $table->uuid('saga_id')->unique()->index();
            $table->uuid('order_id')->index();
            $table->string('current_step', 100);
            $table->string('status', 50);
            $table->json('compensation_data')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->foreign('order_id')
                  ->references('id')
                  ->on('orders')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('saga_states');
    }
};
