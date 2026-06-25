<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_invoice_links', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units')->nullOnDelete();
            $table->foreignId('invoice_id')->constrained('invoices')->cascadeOnDelete();
            $table->string('source_type');
            $table->unsignedBigInteger('source_id');
            $table->decimal('source_line_total', 20, 6)->default('0.000000');
            $table->decimal('allocated_adjustment_total', 20, 6)->default('0.000000');
            $table->decimal('invoice_total', 20, 6)->default('0.000000');
            $table->string('status')->default('active');
            $table->timestamps();

            $table->unique(['invoice_id', 'source_type', 'source_id'], 'sales_invoice_links_invoice_source_uk');
            $table->index(['tenant_id', 'organization_unit_id'], 'sales_invoice_links_scope_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_invoice_links');
    }
};
