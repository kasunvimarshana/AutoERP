<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('stock_returns', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('warehouse_id')->index();
            $table->string('return_number', 100);
            $table->string('return_type', 20);
            $table->string('status', 20)->default('draft');
            $table->unsignedBigInteger('original_order_id')->nullable();
            $table->string('original_order_type', 50)->nullable();
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->unsignedBigInteger('supplier_id')->nullable();
            $table->text('reason')->nullable();
            $table->decimal('total_amount', 15, 4)->nullable();
            $table->decimal('restocking_fee', 15, 4)->nullable();
            $table->string('credit_memo_number', 100)->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->unsignedBigInteger('completed_by')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['tenant_id', 'return_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_returns');
    }
};
