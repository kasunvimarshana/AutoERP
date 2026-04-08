<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commercial_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('party_id')->nullable()->constrained('parties')->nullOnDelete();
            $table->foreignId('bill_to_party_id')->nullable()->constrained('parties')->nullOnDelete();
            $table->foreignId('ship_to_party_id')->nullable()->constrained('parties')->nullOnDelete();
            $table->foreignId('warehouse_id')->nullable()->constrained('warehouses')->nullOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units')->nullOnDelete();
            $table->foreignId('currency_id')->constrained('currencies')->cascadeOnDelete();
            $table->string('document_number');
            $table->string('document_type');
            $table->string('document_subtype')->nullable();
            $table->string('reference_number')->nullable();
            $table->string('status')->default("draft");
            $table->string('fulfillment_status')->default("pending");
            $table->date('document_date');
            $table->date('due_date')->nullable();
            $table->timestamp('posted_at')->nullable();
            $table->timestamp('fulfilled_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->decimal('subtotal_amount', 19, 4)->default(0);
            $table->decimal('discount_amount', 19, 4)->default(0);
            $table->decimal('tax_amount', 19, 4)->default(0);
            $table->decimal('grand_total_amount', 19, 4)->default(0);
            $table->decimal('cost_total_amount', 19, 4)->default(0);
            $table->json('metadata')->nullable();
            $table->unique(['tenant_id', 'document_number']);
            $table->index(['tenant_id', 'document_type']);
            $table->index(['tenant_id', 'party_id']);
            $table->index(['tenant_id', 'document_date']);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commercial_documents');
    }
};
