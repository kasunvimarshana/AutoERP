<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('customer_id')->index();
            $table->string('customer_email');
            $table->json('items');
            $table->decimal('total_amount', 10, 2);
            $table->enum('status', ['pending', 'processing', 'confirmed', 'failed', 'cancelled'])
                  ->default('pending');
            $table->uuid('saga_id')->nullable()->index();
            $table->string('saga_state', 50)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
