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
        Schema::create('bill_payments', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('tenant_id');
            $table->ulid('organization_id');
            $table->ulid('bill_id');
            $table->ulid('vendor_id');
            $table->string('payment_code', 50)->unique();
            $table->string('payment_method', 30);
            $table->string('reference_number', 100)->nullable();
            $table->date('payment_date');
            $table->decimal('amount', 20, 6);
            $table->string('transaction_id', 100)->nullable();
            $table->text('notes')->nullable();
            $table->boolean('reconciled')->default(false);
            $table->timestamp('reconciled_at')->nullable();
            $table->ulid('reconciled_by')->nullable();
            $table->ulid('recorded_by')->nullable();
            $table->ulid('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Foreign keys
            $table->foreign('bill_id')
                ->references('id')
                ->on('bills')
                ->onDelete('cascade');

            // Indexes
            $table->index(['tenant_id', 'organization_id']);
            $table->index(['tenant_id', 'bill_id']);
            $table->index(['tenant_id', 'vendor_id']);
            $table->index(['tenant_id', 'payment_date']);
            $table->index(['tenant_id', 'payment_code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bill_payments');
    }
};
