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
            $table->unsignedBigInteger('tenant_id');
            $table->foreignId('pos_order_id')->constrained('pos_orders');
            $table->string('method', 50);
            $table->decimal('amount', 15, 4);
            $table->string('currency', 3);
            $table->string('reference')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'pos_order_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pos_payments');
    }
};
