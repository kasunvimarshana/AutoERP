<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rental_invoice_links', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units')->nullOnDelete();
            $table->foreignId('agreement_id')->constrained('rental_agreements')->cascadeOnDelete();
            $table->foreignId('charge_id')->constrained('rental_charges')->restrictOnDelete();
            $table->foreignId('invoice_id')->constrained('invoices')->restrictOnDelete();
            $table->foreignId('invoice_line_id')->nullable()->constrained('invoice_lines')->nullOnDelete();
            $table->decimal('invoiced_quantity', 20, 6);
            $table->decimal('invoiced_amount', 20, 6);
            $table->string('status', 20)->default('active');
            $table->timestamps();

            $table->unique(['charge_id', 'invoice_id'], 'rental_invoice_links_charge_invoice_uk');
            $table->index(['tenant_id', 'organization_unit_id'], 'rental_invoice_links_tenant_org_idx');
            $table->index(['agreement_id', 'invoice_id'], 'rental_invoice_links_agreement_invoice_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rental_invoice_links');
    }
};
