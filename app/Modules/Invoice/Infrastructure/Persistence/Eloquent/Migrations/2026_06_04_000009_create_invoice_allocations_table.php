<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoice_allocations', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete()->comment('Multi-tenant owner reference');
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units', 'id')->nullOnDelete()->comment('Branch or department ownership');
            $table->foreignId('invoice_id')->constrained('invoices', 'id')->cascadeOnDelete();

            $table->foreignId('payment_id')->nullable()->constrained('payments', 'id')->nullOnDelete();
            $table->foreignId('allocation_id')->nullable()->constrained('payment_allocations', 'id')->nullOnDelete();
            $table->decimal('allocated_amount', 20, 4)->default(0);
            $table->timestamp('allocated_at')->nullable();
            $table->string('status')->default('active')->comment('active, reversed, pending');
            $table->json('metadata_json')->nullable()->comment('Non-domain metadata such as import keys or integration hints');

            $table->timestamps();

            $table->index(['tenant_id', 'invoice_id', 'status'], 'invoice_allocations_invoice_status_idx');
            $table->index(['tenant_id', 'payment_id'], 'invoice_allocations_payment_idx');
            $table->index(['tenant_id', 'allocation_id'], 'invoice_allocations_allocation_idx');
            $table->index(['tenant_id', 'allocated_at'], 'invoice_allocations_allocated_at_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_allocations');
    }
};
