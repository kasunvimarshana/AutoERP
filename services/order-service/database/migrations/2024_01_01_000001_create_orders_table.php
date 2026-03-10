<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Create orders table
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('tenant_id')->index();
            $table->string('user_id')->index()->comment('Cross-service Auth Service user ID');
            $table->string('status')->default('pending')
                  ->comment('pending|payment_captured|confirmed|shipped|delivered|cancelled|failed');
            $table->string('currency', 3)->default('USD');
            $table->decimal('subtotal',         12, 4)->default(0);
            $table->decimal('tax_amount',       12, 4)->default(0);
            $table->decimal('shipping_amount',  12, 4)->default(0);
            $table->decimal('discount_amount',  12, 4)->default(0);
            $table->decimal('total_amount',     12, 4)->default(0);
            $table->string('payment_id')->nullable();
            $table->string('payment_status')->nullable()->comment('pending|captured|refunded|failed');
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->json('shipping_address')->nullable();
            $table->string('cancellation_reason')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'user_id']);
            $table->index('payment_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
