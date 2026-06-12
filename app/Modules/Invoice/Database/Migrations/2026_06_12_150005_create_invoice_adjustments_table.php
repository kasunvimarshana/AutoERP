<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoice_adjustments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units', 'id')->nullOnDelete();
            $table->foreignId('invoice_id')->constrained('invoices', 'id')->cascadeOnDelete();
            $table->string('source_adjustment_type')->nullable();
            $table->unsignedBigInteger('source_adjustment_id')->nullable();
            $table->string('source_type')->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->string('name');
            $table->enum('adjustment_type', [
                'discount',
                'tax',
                'freight',
                'charge',
                'credit_note',
                'debit_note',
                'withholding',
                'rounding',
                'other',
            ]);
            $table->enum('effect', ['increase', 'decrease']);
            $table->enum('calculation_type', ['fixed', 'percentage'])->default('fixed');
            $table->decimal('rate', 20, 6)->default('0');
            $table->decimal('amount', 20, 6)->default('0');
            $table->enum('allocation_method', ['proportional', 'manual', 'first_invoice', 'last_invoice'])->default('manual');
            $table->boolean('is_system_generated')->default(false);
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index('invoice_id', 'invoice_adjustments_invoice_idx');
            $table->index(['source_adjustment_type', 'source_adjustment_id'], 'invoice_adjustments_source_adjustment_idx');
            $table->index(['source_type', 'source_id'], 'invoice_adjustments_source_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_adjustments');
    }
};
