<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->string('customer_id')->index();
            $table->enum('status', ['pending', 'confirmed', 'cancelled', 'failed'])
                  ->default('pending')
                  ->index();
            $table->decimal('total_amount', 12, 2);
            $table->char('currency', 3)->default('USD');
            $table->json('items');
            $table->json('metadata')->nullable();
            $table->string('saga_id', 36)->nullable()->unique()->index();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'customer_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
