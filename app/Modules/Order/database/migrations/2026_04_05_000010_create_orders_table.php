<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', static function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('tenant_id');
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->unsignedBigInteger('org_unit_id')->nullable();
            $table->string('reference_number', 50);
            $table->enum('type', ['purchase', 'sale']);
            $table->unsignedBigInteger('supplier_id')->nullable();
            $table->foreign('supplier_id')->references('id')->on('suppliers')->nullOnDelete();
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->foreign('customer_id')->references('id')->on('customers')->nullOnDelete();
            $table->enum('status', ['draft', 'confirmed', 'in_progress', 'completed', 'cancelled', 'on_hold'])->default('draft');
            $table->enum('payment_status', ['pending', 'partial', 'paid', 'overpaid', 'refunded'])->default('pending');
            $table->date('order_date');
            $table->date('expected_date')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->string('currency', 3)->default('USD');
            $table->decimal('exchange_rate', 20, 10)->default(1.0000000000);
            $table->decimal('subtotal', 20, 6)->default(0);
            $table->decimal('discount_amount', 20, 6)->default(0);
            $table->decimal('tax_amount', 20, 6)->default(0);
            $table->decimal('shipping_amount', 20, 6)->default(0);
            $table->decimal('total_amount', 20, 6)->default(0);
            $table->unsignedBigInteger('warehouse_id')->nullable();
            $table->foreign('warehouse_id')->references('id')->on('warehouses')->nullOnDelete();
            $table->json('billing_address')->nullable();
            $table->json('shipping_address')->nullable();
            $table->text('notes')->nullable();
            $table->text('internal_notes')->nullable();
            $table->json('metadata')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'reference_number']);
            $table->index(['tenant_id', 'type']);
            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'payment_status']);
            $table->index(['supplier_id']);
            $table->index(['customer_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
