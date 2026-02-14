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
            $table->uuid('tenant_id')->index();
            $table->uuid('branch_id')->index();
            $table->uuid('customer_id')->index();
            $table->string('order_number', 100)->unique();
            $table->date('order_date')->index();
            $table->enum('status', ['pending', 'confirmed', 'processing', 'completed', 'cancelled'])->default('pending')->index();
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->decimal('tax_amount', 12, 2)->default(0);
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->decimal('grand_total', 12, 2)->default(0);
            $table->text('notes')->nullable();
            $table->json('settings')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('branch_id')->references('id')->on('organizations')->onDelete('cascade');
            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('cascade');
            
            $table->index(['tenant_id', 'status']);
            $table->index(['branch_id', 'status']);
            $table->index(['customer_id', 'status']);
            $table->index(['tenant_id', 'order_date']);
            $table->index(['branch_id', 'order_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
