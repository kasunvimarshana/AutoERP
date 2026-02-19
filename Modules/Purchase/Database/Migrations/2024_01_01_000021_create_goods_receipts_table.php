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
        Schema::create('goods_receipts', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('tenant_id');
            $table->ulid('organization_id');
            $table->ulid('vendor_id');
            $table->ulid('purchase_order_id');
            $table->string('receipt_code', 50)->unique();
            $table->string('reference', 100)->nullable();
            $table->string('status', 20)->default('draft');
            $table->date('receipt_date');
            $table->string('delivery_note', 100)->nullable();
            $table->text('notes')->nullable();
            $table->ulid('received_by')->nullable();
            $table->ulid('created_by')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('posted_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->string('cancellation_reason')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['tenant_id', 'organization_id']);
            $table->index(['tenant_id', 'vendor_id']);
            $table->index(['tenant_id', 'purchase_order_id']);
            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'receipt_date']);
            $table->index(['tenant_id', 'receipt_code']);

            // Composite indexes
            $table->index(['tenant_id', 'purchase_order_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('goods_receipts');
    }
};
