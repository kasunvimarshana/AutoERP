<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoice_source_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants', 'id', indexName: 'invoice_source_lines_tenant_fk')->restrictOnDelete();
            $table->foreignId('organization_unit_id')->nullable();
            $table->foreignId('invoice_id');
            $table->foreignId('invoice_line_id')->nullable();
            $table->string('source_type');
            $table->unsignedBigInteger('source_id');
            $table->string('source_line_type');
            $table->unsignedBigInteger('source_line_id');
            $table->decimal('source_quantity', 20, 6)->default('0');
            $table->decimal('previously_invoiced_quantity', 20, 6)->default('0');
            $table->decimal('invoiced_quantity', 20, 6)->default('0');
            $table->decimal('remaining_quantity', 20, 6)->default('0');
            $table->decimal('source_unit_price', 20, 6)->default('0');
            $table->decimal('source_line_total', 20, 6)->default('0');
            $table->decimal('invoiced_line_total', 20, 6)->default('0');
            $table->timestamps();

            $table->index(['source_type', 'source_id'], 'invoice_source_lines_source_ix');
            $table->index(['source_line_type', 'source_line_id'], 'invoice_source_lines_line_ix');

            $table->unique(['id', 'tenant_id'], 'invoice_source_lines_id_tenant_uk');
            $table->foreign(['organization_unit_id', 'tenant_id'], 'invoice_source_lines_organization_unit_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('organization_units')
                ->restrictOnDelete();
            $table->foreign(['invoice_id', 'tenant_id'], 'invoice_source_lines_invoice_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('invoices')
                ->cascadeOnDelete();
            $table->foreign(['invoice_line_id', 'tenant_id'], 'invoice_source_lines_invoice_line_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('invoice_lines')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_source_lines');
    }
};
