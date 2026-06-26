<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants', 'id', indexName: 'payment_allocations_tenant_fk')->restrictOnDelete();
            $table->foreignId('organization_unit_id')->nullable();
            $table->foreignId('payment_id');
            $table->foreignId('invoice_id');
            $table->decimal('invoice_total', 20, 6);
            $table->decimal('invoice_balance_before', 20, 6);
            $table->decimal('previously_allocated_amount', 20, 6)->default('0');
            $table->decimal('allocated_amount', 20, 6);
            $table->decimal('invoice_balance_after', 20, 6);
            $table->date('allocation_date');
            $table->string('allocation_method', 50)->default('specific_invoice');
            $table->enum('status', ['pending', 'active', 'reversed', 'void'])->default('pending');
            $table->timestamp('realized_at')->nullable();
            $table->unsignedBigInteger('realized_by')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['payment_id', 'invoice_id'], 'payment_allocations_payment_invoice_ix');
            $table->index('invoice_id', 'payment_allocations_invoice_ix');

            $table->unique(['id', 'tenant_id'], 'payment_allocations_id_tenant_uk');
            $table->foreign(['organization_unit_id', 'tenant_id'], 'payment_allocations_organization_unit_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('organization_units')
                ->restrictOnDelete();
            $table->foreign(['payment_id', 'tenant_id'], 'payment_allocations_payment_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('payments')
                ->cascadeOnDelete();
            $table->foreign(['invoice_id', 'tenant_id'], 'payment_allocations_invoice_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('invoices')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_allocations');
    }
};
