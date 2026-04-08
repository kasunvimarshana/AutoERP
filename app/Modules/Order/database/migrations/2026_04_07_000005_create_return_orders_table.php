<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('return_orders', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->string('return_number', 50);
            $table->date('return_date');
            // sales_return, purchase_return
            $table->string('type', 30);
            // The source order (sales or purchase)
            $table->string('source_order_type', 100)->nullable();
            $table->uuid('source_order_id')->nullable();
            // draft, confirmed, restocked, credited, cancelled
            $table->string('status', 30)->default('draft');
            $table->string('currency_code', 10)->default('USD');
            $table->decimal('subtotal', 15, 4)->default(0);
            $table->decimal('tax_amount', 15, 4)->default(0);
            $table->decimal('restocking_fee', 15, 4)->default(0);
            $table->decimal('refund_amount', 15, 4)->default(0);
            $table->text('reason')->nullable();
            $table->text('notes')->nullable();
            $table->string('resolution', 50)->default('refund'); // refund, credit_memo, exchange
            $table->uuid('credit_memo_id')->nullable();
            $table->uuid('warehouse_id')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'return_number']);
            $table->index(['tenant_id', 'status']);
            $table->index(['source_order_type', 'source_order_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('return_orders');
    }
};
