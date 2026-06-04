<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoice_links', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete()->comment('Multi-tenant owner reference');
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units', 'id')->nullOnDelete()->comment('Branch or department ownership');
            $table->foreignId('invoice_id')->constrained('invoices', 'id')->cascadeOnDelete();
            $table->foreignId('linked_invoice_id')->constrained('invoices', 'id')->cascadeOnDelete();

            $table->string('link_type')->comment('credit, debit, reversal, correction, consolidation, split, related');
            $table->decimal('amount', 20, 4)->default(0);
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->unique(['tenant_id', 'invoice_id', 'linked_invoice_id', 'link_type'], 'invoice_links_unique');
            $table->index(['tenant_id', 'invoice_id', 'link_type'], 'invoice_links_invoice_type_idx');
            $table->index(['tenant_id', 'linked_invoice_id'], 'invoice_links_linked_invoice_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_links');
    }
};
