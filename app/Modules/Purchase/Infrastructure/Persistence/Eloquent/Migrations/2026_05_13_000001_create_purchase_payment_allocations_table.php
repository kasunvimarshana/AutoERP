<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_payment_allocations', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete()->comment('Multi-tenant owner reference');
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units', 'id')->nullOnDelete()->comment('Branch or department ownership');
            $table->json('metadata')->nullable()->comment('Extensible custom dynamic data');

            $table->foreignId('invoice_id')->constrained('invoices', 'id')->cascadeOnDelete();
            $table->foreignId('payment_id')->nullable()->constrained('payments', 'id')->nullOnDelete();
            $table->foreignId('advance_payment_id')->nullable()->constrained('advance_payments', 'id')->nullOnDelete();
            $table->decimal('allocated_amount', 20, 4);
            $table->foreignId('currency_id')->nullable()->constrained('currencies')->nullOnDelete();
            $table->decimal('base_allocated_amount', 20, 4)->nullable();
            $table->string('status')->default('active')->comment('active, reversed');
            $table->timestamp('allocated_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();

            $table->timestamps();

            $table->index(['tenant_id', 'invoice_id', 'status'], 'purchase_payment_allocations_invoice_status_idx');
            $table->index(['tenant_id', 'payment_id', 'status'], 'purchase_payment_allocations_payment_status_idx');
            $table->index(['tenant_id', 'advance_payment_id', 'status'], 'purchase_payment_allocations_advance_status_idx');
            $table->unique(
                ['tenant_id', 'invoice_id', 'payment_id'],
                'purchase_payment_allocations_payment_uk'
            );
            $table->unique(
                ['tenant_id', 'invoice_id', 'advance_payment_id'],
                'purchase_payment_allocations_advance_uk'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_payment_allocations');
    }
};
