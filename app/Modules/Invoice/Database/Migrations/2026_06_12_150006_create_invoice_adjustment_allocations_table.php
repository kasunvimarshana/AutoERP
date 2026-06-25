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
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->restrictOnDelete();
            $table->foreignId('organization_unit_id')->nullable();
            $table->foreignId('invoice_id');
            $table->foreignId('invoice_adjustment_id')->nullable();
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

            $table->unique(['id', 'tenant_id'], 'invoice_adjustment_allocations_id_tenant_uk');
            $table->foreign(['organization_unit_id', 'tenant_id'], 'invoice_adjustment_allocations_organization_unit_028225a6_fk')
                ->references(['id', 'tenant_id'])
                ->on('organization_units')
                ->restrictOnDelete();
            $table->foreign(['invoice_id', 'tenant_id'], 'invoice_adjustment_allocations_invoice_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('invoices')
                ->cascadeOnDelete();
            $table->foreign(['invoice_adjustment_id', 'tenant_id'], 'invoice_adjustment_allocations_invoice_adjustmen_e3ce9c12_fk')
                ->references(['id', 'tenant_id'])
                ->on('invoice_adjustments')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_adjustment_allocations');
    }
};
