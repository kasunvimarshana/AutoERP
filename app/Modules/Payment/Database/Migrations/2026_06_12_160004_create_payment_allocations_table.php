<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const ACTIVE_IDENTITY_SLOT = 1;

    public function up(): void
    {
        Schema::create('payment_allocations', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1);
            $table->foreignId('tenant_id')->constrained('tenants', 'id', indexName: 'payment_allocations_tenant_fk')->restrictOnDelete();
            $table->foreignId('organization_unit_id')->nullable();
            $table->foreignId('payment_id');
            $table->foreignId('invoice_id');
            $table->unsignedTinyInteger('active_identity_slot')
                ->nullable()
                ->default(self::ACTIVE_IDENTITY_SLOT);
            $table->string('invoice_number_snapshot', 100);
            $table->date('invoice_date_snapshot')->nullable();
            $table->string('invoice_currency_code_snapshot', 20)->nullable();
            $table->decimal('invoice_total', 20, 6);
            $table->decimal('invoice_balance_before', 20, 6);
            $table->decimal('previously_allocated_amount', 20, 6)->default('0');
            $table->decimal('allocated_amount', 20, 6);
            $table->decimal('invoice_balance_after', 20, 6);
            $table->date('allocation_date');
            $table->string('allocation_method', 50)->default('specific_invoice');
            $table->string('status', 40)->default('pending');
            $table->timestamp('realized_at')->nullable();
            $table->unsignedBigInteger('realized_by')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(
                ['payment_id', 'invoice_id', 'active_identity_slot'],
                'payment_allocations_payment_invoice_active_uk',
            );
            $table->index('invoice_id', 'payment_allocations_invoice_ix');
            $table->unique(['id', 'tenant_id'], 'payment_allocations_id_tenant_uk');
            $table->foreign(['organization_unit_id', 'tenant_id'], 'payment_allocations_organization_unit_id_tenant_fk')->references(['id', 'tenant_id'])->on('organization_units')->restrictOnDelete();
            $table->foreign(['payment_id', 'tenant_id'], 'payment_allocations_payment_id_tenant_fk')->references(['id', 'tenant_id'])->on('payments')->restrictOnDelete();
            $table->foreign(['invoice_id', 'tenant_id'], 'payment_allocations_invoice_id_tenant_fk')->references(['id', 'tenant_id'])->on('invoices')->restrictOnDelete();
            $table->foreign(['realized_by', 'tenant_id'], 'payment_allocations_realized_by_tenant_fk')->references(['id', 'tenant_id'])->on('users')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_allocations');
    }
};