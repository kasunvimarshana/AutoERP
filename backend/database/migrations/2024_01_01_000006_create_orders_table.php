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
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('order_number', 30)->unique();
            $table->unsignedBigInteger('customer_id')->index();
            $table->string('customer_name');
            $table->string('customer_email');
            $table->string('status', 30)->default('pending')->index();
            $table->decimal('subtotal', 12, 4)->default(0);
            $table->decimal('tax', 12, 4)->default(0);
            $table->decimal('discount', 12, 4)->default(0);
            $table->decimal('total', 12, 4)->default(0);
            $table->string('currency', 3)->default('USD');
            $table->text('notes')->nullable();
            $table->json('shipping_address')->nullable();
            $table->json('billing_address')->nullable();
            $table->json('metadata')->nullable();
            $table->string('saga_id', 36)->nullable()->index();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'customer_id']);
            $table->index(['tenant_id', 'created_at']);
            $table->foreign('customer_id')->references('id')->on('users')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
