<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('advance_payment_allocations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete()->comment('Multi-tenant owner reference');
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units', 'id')->nullOnDelete()->comment('Branch or department ownership');
            $table->json('metadata')->nullable()->comment('Extensible custom dynamic data');

            $table->foreignId('advance_payment_id')->constrained('advance_payments')->cascadeOnDelete();
            $table->string('invoice_type')->comment('the invoice/credit note being settled');
            $table->unsignedBigInteger('invoice_id');
            $table->unsignedBigInteger('invoice_line_id')->nullable();
            $table->string('reference')->nullable();
            $table->decimal('allocated_amount', 20, 4);
            $table->foreignId('currency_id')->nullable()->constrained('currencies')->nullOnDelete();
            $table->decimal('base_allocated_amount', 20, 4)->nullable();
            $table->date('allocation_date')->nullable();
            $table->string('status')->default('active')->comment('active, reversed');

            $table->timestamps();

            $table->unique(['tenant_id', 'advance_payment_id', 'invoice_type', 'invoice_id'], 'advance_payment_allocations_invoice_uk');
            $table->index(['tenant_id', 'invoice_type', 'invoice_id'], 'advance_payment_allocations_invoice_lookup_idx');
            $table->index(['tenant_id', 'advance_payment_id', 'status'], 'advance_payment_allocations_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('advance_payment_allocations');
    }
};
