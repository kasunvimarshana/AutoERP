<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rental_payment_links', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units')->nullOnDelete();
            $table->foreignId('agreement_id')->constrained('rental_agreements')->cascadeOnDelete();
            $table->foreignId('payment_id')->constrained('payments')->restrictOnDelete();
            $table->foreignId('invoice_id')->nullable()->constrained('invoices')->nullOnDelete();
            $table->string('link_type', 20);
            $table->decimal('amount', 20, 6);
            $table->string('status', 20)->default('active');
            $table->timestamps();

            $table->unique(['agreement_id', 'payment_id'], 'rental_payment_links_agreement_payment_uk');
            $table->index(['tenant_id', 'organization_unit_id'], 'rental_payment_links_tenant_org_idx');
            $table->index(['agreement_id', 'link_type'], 'rental_payment_links_agreement_type_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rental_payment_links');
    }
};
