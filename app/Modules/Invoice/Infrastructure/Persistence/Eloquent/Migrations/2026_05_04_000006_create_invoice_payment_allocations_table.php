<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoice_payment_allocations', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('invoice_id')->constrained('invoices')->cascadeOnDelete();
            $table->foreignId('payment_id')->constrained('payments')->cascadeOnDelete();

            $table->decimal('allocated_amount', 20, 4);
            $table->date('allocated_date')->nullable();

            $table->string('status', 50)->default('active');
            // active, reversed

            $table->timestamps();

            $table->unique(['invoice_id', 'payment_id']);
            $table->index(['invoice_id']);
            $table->index(['payment_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_payment_allocations');
    }
};
