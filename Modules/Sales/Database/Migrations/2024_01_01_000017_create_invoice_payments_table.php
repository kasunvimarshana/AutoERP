<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('invoice_payments', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('tenant_id');
            $table->ulid('invoice_id');
            $table->string('payment_code', 50)->unique();
            $table->date('payment_date');
            $table->decimal('amount', 20, 6)->default(0);
            $table->string('payment_method', 50);
            $table->string('reference_number', 100)->nullable();
            $table->text('notes')->nullable();
            $table->boolean('reconciled')->default(false);
            $table->timestamp('reconciled_at')->nullable();
            $table->ulid('reconciled_by')->nullable();
            $table->ulid('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['tenant_id', 'invoice_id']);
            $table->index(['tenant_id', 'payment_method']);
            $table->index(['tenant_id', 'payment_date']);
            $table->index(['tenant_id', 'payment_code']);
            $table->index(['tenant_id', 'reconciled']);

            // Composite indexes for common queries
            $table->index(['tenant_id', 'invoice_id', 'payment_date']);
            $table->index(['tenant_id', 'reconciled', 'payment_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoice_payments');
    }
};
