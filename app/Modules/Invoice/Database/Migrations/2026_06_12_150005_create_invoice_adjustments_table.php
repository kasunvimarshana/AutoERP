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
            $table->foreignId('tenant_id')->constrained('tenants', 'id', indexName: 'invoice_adjustments_tenant_fk')->restrictOnDelete();
            $table->foreignId('organization_unit_id')->nullable();
            $table->foreignId('invoice_id');
            $table->string('source_adjustment_type')->nullable();
            $table->unsignedBigInteger('source_adjustment_id')->nullable();
            $table->string('source_type')->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->string('name');
            $table->string('adjustment_type', 40);
            $table->string('effect', 40);
            $table->string('calculation_type', 40)->default('fixed');
            $table->decimal('rate', 20, 6)->default('0');
            $table->decimal('amount', 20, 6)->default('0');
            $table->string('allocation_method', 40)->default('manual');
            $table->boolean('is_system_generated')->default(false);
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index('invoice_id', 'invoice_adjustments_invoice_ix');
            $table->index(['source_adjustment_type', 'source_adjustment_id'], 'invoice_adjustments_source_adjustment_ix');
            $table->index(['source_type', 'source_id'], 'invoice_adjustments_source_ix');

            $table->unique(['id', 'tenant_id'], 'invoice_adjustments_id_tenant_uk');
            $table->foreign(['organization_unit_id', 'tenant_id'], 'invoice_adjustments_organization_unit_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('organization_units')
                ->restrictOnDelete();
            $table->foreign(['invoice_id', 'tenant_id'], 'invoice_adjustments_invoice_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('invoices')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_adjustments');
    }
};
