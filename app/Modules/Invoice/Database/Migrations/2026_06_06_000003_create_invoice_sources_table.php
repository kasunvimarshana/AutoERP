<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoice_sources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units', 'id')->nullOnDelete();
            $table->foreignId('invoice_id')->constrained('invoices', 'id')->cascadeOnDelete();
            $table->string('source_type');
            $table->unsignedBigInteger('source_id');
            $table->string('source_document_number')->nullable();
            $table->date('source_document_date')->nullable();
            $table->decimal('source_subtotal', 20, 6)->default('0');
            $table->decimal('source_adjustment_total', 20, 6)->default('0');
            $table->decimal('source_grand_total', 20, 6)->default('0');
            $table->decimal('invoiced_amount', 20, 6)->default('0');
            $table->decimal('allocated_adjustment_amount', 20, 6)->default('0');
            $table->timestamps();

            $table->unique(['invoice_id', 'source_type', 'source_id'], 'invoice_sources_invoice_source_uk');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_sources');
    }
};
