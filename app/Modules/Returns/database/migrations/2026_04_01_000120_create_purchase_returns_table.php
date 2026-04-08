<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_returns', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->index();
            $table->uuid('purchase_order_id')->nullable();
            $table->string('supplier_id');
            $table->string('warehouse_id');
            $table->string('reference');
            $table->string('status')->default('draft');
            $table->date('return_date');
            $table->text('reason')->nullable();
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->string('credit_memo_number')->nullable();
            $table->decimal('refund_amount', 15, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['tenant_id', 'reference']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_returns');
    }
};
