<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('returns', static function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('tenant_id');
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->unsignedBigInteger('org_unit_id')->nullable();
            $table->string('reference_number', 50);
            $table->enum('type', ['purchase_return', 'sale_return']);
            $table->unsignedBigInteger('original_order_id')->nullable();
            $table->foreign('original_order_id')->references('id')->on('orders')->nullOnDelete();
            $table->unsignedBigInteger('supplier_id')->nullable();
            $table->foreign('supplier_id')->references('id')->on('suppliers')->nullOnDelete();
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->foreign('customer_id')->references('id')->on('customers')->nullOnDelete();
            $table->unsignedBigInteger('warehouse_id')->nullable();
            $table->foreign('warehouse_id')->references('id')->on('warehouses')->nullOnDelete();
            $table->enum('status', ['draft', 'submitted', 'approved', 'processing', 'completed', 'rejected', 'cancelled'])->default('draft');
            $table->date('return_date');
            $table->enum('reason', ['defective', 'wrong_item', 'damaged', 'overdelivery', 'quality_issue', 'other']);
            $table->decimal('subtotal', 20, 6)->default(0);
            $table->decimal('tax_amount', 20, 6)->default(0);
            $table->decimal('total_amount', 20, 6)->default(0);
            $table->unsignedBigInteger('restock_location_id')->nullable();
            $table->foreign('restock_location_id')->references('id')->on('locations')->nullOnDelete();
            $table->string('credit_memo_number', 100)->nullable();
            $table->timestamp('credit_memo_issued_at')->nullable();
            $table->decimal('fee_amount', 20, 6)->default(0);
            $table->string('fee_description', 255)->nullable();
            $table->text('notes')->nullable();
            $table->text('internal_notes')->nullable();
            $table->json('metadata')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->foreign('approved_by')->references('id')->on('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->unsignedBigInteger('processed_by')->nullable();
            $table->foreign('processed_by')->references('id')->on('users')->nullOnDelete();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'reference_number']);
            $table->index(['tenant_id', 'type']);
            $table->index(['tenant_id', 'status']);
            $table->index(['original_order_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('returns');
    }
};
