<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoice_credit_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units', 'id')->nullOnDelete();
            $table->string('credit_source_type');
            $table->unsignedBigInteger('credit_source_id');
            $table->foreignId('invoice_id')->constrained('invoices', 'id')->cascadeOnDelete();
            $table->decimal('invoice_total', 20, 6)->default('0');
            $table->decimal('previously_allocated_amount', 20, 6)->default('0');
            $table->decimal('allocated_amount', 20, 6)->default('0');
            $table->decimal('remaining_invoice_balance', 20, 6)->default('0');
            $table->timestamps();

            $table->index(['credit_source_type', 'credit_source_id'], 'invoice_credit_allocations_source_idx');
            $table->index('invoice_id', 'invoice_credit_allocations_invoice_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_credit_allocations');
    }
};
