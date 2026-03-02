<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vendor_bills', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('vendor_id');
            $table->unsignedBigInteger('purchase_order_id')->nullable();
            $table->string('bill_number');
            $table->string('status')->default('draft')->comment('draft/posted/paid/cancelled');
            $table->date('bill_date');
            $table->date('due_date')->nullable();
            $table->decimal('total_amount', 20, 4)->default(0);
            $table->decimal('paid_amount', 20, 4)->default(0);
            $table->timestamps();

            $table->unique(['tenant_id', 'bill_number']);
            $table->foreign('vendor_id')->references('id')->on('vendors')->restrictOnDelete();
            $table->foreign('purchase_order_id')->references('id')->on('purchase_orders')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_bills');
    }
};
