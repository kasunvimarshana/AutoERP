<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoice_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants', 'id', indexName: 'invoice_lines_tenant_fk')->restrictOnDelete();
            $table->foreignId('organization_unit_id')->nullable();
            $table->foreignId('invoice_id');
            $table->unsignedInteger('line_number');
            $table->unsignedBigInteger('item_id')->nullable();
            $table->text('description');
            $table->string('line_type', 40)->default('item');
            $table->decimal('quantity', 20, 6)->default('0');
            $table->foreignId('uom_id')->nullable();
            $table->decimal('unit_price', 20, 6)->default('0');
            $table->decimal('discount_amount', 20, 6)->default('0');
            $table->decimal('tax_amount', 20, 6)->default('0');
            $table->decimal('charge_amount', 20, 6)->default('0');
            $table->decimal('line_total', 20, 6)->default('0');
            $table->string('source_line_type')->nullable();
            $table->unsignedBigInteger('source_line_id')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index('invoice_id', 'invoice_lines_invoice_ix');
            $table->index('item_id', 'invoice_lines_item_ix');
            $table->index(['source_line_type', 'source_line_id'], 'invoice_lines_source_line_ix');

            $table->unique(['id', 'tenant_id'], 'invoice_lines_id_tenant_uk');
            $table->foreign(['organization_unit_id', 'tenant_id'], 'invoice_lines_organization_unit_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('organization_units')
                ->restrictOnDelete();
            $table->foreign(['invoice_id', 'tenant_id'], 'invoice_lines_invoice_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('invoices')
                ->cascadeOnDelete();
            $table->foreign(['uom_id', 'tenant_id'], 'invoice_lines_uom_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('unit_of_measures')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_lines');
    }
};
