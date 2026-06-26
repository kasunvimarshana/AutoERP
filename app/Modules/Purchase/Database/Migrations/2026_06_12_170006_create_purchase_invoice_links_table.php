<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_invoice_links', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants', indexName: 'purchase_invoice_links_tenant_fk')->restrictOnDelete();
            $table->foreignId('organization_unit_id')->nullable();
            $table->foreignId('invoice_id');
            $table->string('source_type');
            $table->unsignedBigInteger('source_id');
            $table->decimal('source_line_total', 20, 6)->default('0.000000');
            $table->decimal('allocated_adjustment_total', 20, 6)->default('0.000000');
            $table->decimal('invoice_total', 20, 6)->default('0.000000');
            $table->string('status')->default('active');
            $table->timestamps();

            $table->unique(['invoice_id', 'source_type', 'source_id'], 'purchase_invoice_links_invoice_source_uk');
            $table->index(['tenant_id', 'organization_unit_id'], 'purchase_invoice_links_tenant_org_ix');
            $table->index(['source_type', 'source_id'], 'purchase_invoice_links_source_ix');
            $table->index('status', 'purchase_invoice_links_status_ix');

            $table->unique(['id', 'tenant_id'], 'purchase_invoice_links_id_tenant_uk');
            $table->foreign(['organization_unit_id', 'tenant_id'], 'purchase_invoice_links_organization_unit_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('organization_units')
                ->restrictOnDelete();
            $table->foreign(['invoice_id', 'tenant_id'], 'purchase_invoice_links_invoice_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('invoices')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_invoice_links');
    }
};
