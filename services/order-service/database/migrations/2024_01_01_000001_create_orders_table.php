<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table): void {
            $table->id();

            $table->string('order_number', 50)->unique();

            // Customer reference from Keycloak (UUID sub claim) – cross-service, no FK
            $table->string('customer_id', 255)->index();
            $table->string('customer_name', 255);
            $table->string('customer_email', 255);

            $table->enum('status', [
                'pending',
                'confirmed',
                'processing',
                'shipped',
                'delivered',
                'cancelled',
            ])->default('pending');

            $table->decimal('total_amount', 12, 2)->default(0.00);
            $table->decimal('tax_amount', 12, 2)->default(0.00);
            $table->decimal('discount_amount', 12, 2)->default(0.00);

            $table->json('shipping_address')->nullable();
            $table->json('billing_address')->nullable();
            $table->text('notes')->nullable();

            // Saga orchestration
            $table->enum('saga_status', [
                'started',
                'inventory_reserved',
                'payment_processed',
                'completed',
                'compensating',
                'compensated',
                'failed',
            ])->default('started');

            $table->json('saga_compensation_data')->nullable();

            // Timestamp trail
            $table->timestamp('placed_at')->nullable()->index();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Query indexes
            $table->index('status');
            $table->index('saga_status');
            $table->index('customer_email');
            $table->index(['customer_id', 'status']);
            $table->index(['status', 'placed_at']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
