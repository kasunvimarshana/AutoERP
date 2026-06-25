<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoice_adjustment_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units', 'id')->nullOnDelete();
            $table->foreignId('invoice_id')->constrained('invoices', 'id')->cascadeOnDelete();
            $table->foreignId('invoice_adjustment_id')->nullable()->constrained('invoice_adjustments', 'id')->nullOnDelete();
            $table->string('source_adjustment_type');
            $table->unsignedBigInteger('source_adjustment_id');
            $table->string('source_type');
            $table->unsignedBigInteger('source_id');
            $table->string('adjustment_type');
            $table->string('effect');
            $table->string('allocation_method');
            $table->decimal('source_amount', 20, 6)->default('0');
            $table->decimal('previously_allocated_amount', 20, 6)->default('0');
            $table->decimal('allocated_amount', 20, 6)->default('0');
            $table->decimal('remaining_amount', 20, 6)->default('0');
            $table->timestamps();

            $table->unique(
                ['invoice_id', 'source_adjustment_type', 'source_adjustment_id'],
                'invoice_adjustment_allocations_invoice_source_uk'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_adjustment_allocations');
    }
};
