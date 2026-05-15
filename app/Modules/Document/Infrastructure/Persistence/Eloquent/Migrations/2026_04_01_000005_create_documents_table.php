<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units', 'id')->nullOnDelete();
            $table->json('metadata')->nullable();

            $table->foreignId('document_type_id')->constrained('document_types');
            $table->string('document_number');
            $table->date('document_date');
            $table->date('due_date')->nullable();
            $table->foreignId('payment_term_id')->nullable()->constrained('payment_terms')->nullOnDelete();
            $table->string('status')->comment('draft, pending_approval, approved, posted, partially_paid, paid, overdue, void');
            // $table->string('party_type')->nullable()->comment('customer, supplier, etc.');
            $table->foreignId('party_id')->nullable()->constrained('parties');
            $table->decimal('subtotal', 20, 4)->default(0);
            $table->decimal('discount_total', 20, 4)->default(0);
            $table->decimal('tax_total', 20, 4)->default(0);
            $table->decimal('grand_total', 20, 4)->default(0);
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'organization_unit_id', 'document_number'], 'documents_document_number_uk');
            $table->index(['tenant_id', 'organization_unit_id', 'document_type_id', 'status'], 'documents_document_type_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
