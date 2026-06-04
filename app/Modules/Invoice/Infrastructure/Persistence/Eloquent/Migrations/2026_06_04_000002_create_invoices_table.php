<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete()->comment('Multi-tenant owner reference');
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units', 'id')->nullOnDelete()->comment('Branch or department ownership');
            $table->foreignId('invoice_type_id')->constrained('invoice_types', 'id')->restrictOnDelete();

            $table->string('invoice_number');
            $table->date('invoice_date');
            $table->date('due_date')->nullable();
            $table->string('status')->default('draft')->comment('draft, approved, posted, partially_paid, paid, cancelled, reversed');
            $table->string('direction')->comment('payable, receivable, internal');
            $table->string('party_type', 120);
            $table->unsignedBigInteger('party_id');
            $table->string('billing_party_type', 120)->nullable();
            $table->unsignedBigInteger('billing_party_id')->nullable();
            $table->string('currency_code');
            $table->decimal('exchange_rate', 20, 4)->default(1);
            $table->decimal('subtotal_amount', 20, 4)->default(0);
            $table->decimal('discount_amount', 20, 4)->default(0);
            $table->decimal('tax_amount', 20, 4)->default(0);
            $table->decimal('charge_amount', 20, 4)->default(0);
            $table->decimal('rounding_amount', 20, 4)->default(0);
            $table->decimal('total_amount', 20, 4)->default(0);
            $table->decimal('paid_amount', 20, 4)->default(0);
            $table->decimal('credited_amount', 20, 4)->default(0);
            $table->decimal('balance_amount', 20, 4)->default(0);

            $table->string('source_module', 120)->nullable();
            $table->string('source_type', 120)->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->string('source_reference', 180)->nullable();
            $table->json('source_context')->nullable()->comment('Additional source context supplied by owning module');
            $table->unsignedInteger('schema_version')->default(1);
            $table->json('data_json')->nullable()->comment('Dynamic invoice payload for module-specific layout or calculation extras');
            $table->json('metadata_json')->nullable()->comment('Non-domain metadata such as import keys or integration hints');

            $table->timestamp('posted_at')->nullable();
            $table->foreignId('posted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'invoice_type_id', 'invoice_number'], 'invoices_type_number_uk');
            $table->index(['tenant_id', 'invoice_number'], 'invoices_number_idx');
            $table->index(['tenant_id', 'invoice_type_id', 'status'], 'invoices_type_status_idx');
            $table->index(['tenant_id', 'status', 'invoice_date'], 'invoices_status_date_idx');
            $table->index(['tenant_id', 'direction', 'status', 'invoice_date'], 'invoices_direction_status_date_idx');
            $table->index(['tenant_id', 'due_date'], 'invoices_due_date_idx');
            $table->index(['tenant_id', 'party_type', 'party_id'], 'invoices_party_idx');
            $table->index(['tenant_id', 'billing_party_type', 'billing_party_id'], 'invoices_billing_party_idx');
            $table->index(['tenant_id', 'source_module', 'source_type', 'source_id'], 'invoices_source_idx');
            $table->index(['tenant_id', 'posted_at'], 'invoices_posted_at_idx');
            $table->foreign('currency_code', 'invoices_currency_code_fk')->references('code')->on('currencies')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
