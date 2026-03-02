<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_invoices', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('sales_order_id');
            $table->string('invoice_number');
            $table->string('status')->default('draft')->comment('draft/issued/paid/cancelled');
            $table->date('issued_at')->nullable();
            $table->date('due_date')->nullable();
            $table->decimal('total_amount', 20, 4);
            $table->decimal('paid_amount', 20, 4)->default('0.0000');
            $table->timestamps();

            $table->unique(['tenant_id', 'invoice_number']);
            $table->foreign('sales_order_id')->references('id')->on('sales_orders')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_invoices');
    }
};
