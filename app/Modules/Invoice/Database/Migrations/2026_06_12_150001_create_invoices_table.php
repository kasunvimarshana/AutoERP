<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable();
            $table->string('invoice_number', 100);
            $table->enum('invoice_type', ['purchase', 'sales', 'service', 'rental', 'manual', 'credit', 'debit']);
            $table->enum('direction', ['inbound', 'outbound']);
            $table->string('party_type')->nullable();
            $table->unsignedBigInteger('party_id')->nullable();
            $table->date('invoice_date');
            $table->date('due_date')->nullable();
            $table->foreignId('currency_id')->nullable()->constrained('currencies', 'id')->nullOnDelete();
            $table->decimal('exchange_rate', 20, 6)->default('1.000000');
            $table->enum('status', ['draft', 'approved', 'posted', 'partially_paid', 'paid', 'cancelled', 'void'])->default('draft');
            $table->decimal('subtotal', 20, 6)->default('0');
            $table->decimal('discount_total', 20, 6)->default('0');
            $table->decimal('tax_total', 20, 6)->default('0');
            $table->decimal('charge_total', 20, 6)->default('0');
            $table->decimal('adjustment_total', 20, 6)->default('0');
            $table->decimal('grand_total', 20, 6)->default('0');
            $table->decimal('paid_total', 20, 6)->default('0');
            $table->decimal('credit_total', 20, 6)->default('0');
            $table->decimal('balance_due', 20, 6)->default('0');
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('posted_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'invoice_number'], 'invoices_tenant_number_uk');
            $table->index(['tenant_id', 'organization_unit_id'], 'invoices_tenant_org_idx');
            $table->index(['invoice_type', 'direction', 'status'], 'invoices_type_direction_status_idx');
            $table->index(['party_type', 'party_id'], 'invoices_party_idx');

            $table->unique(['id', 'tenant_id'], 'invoices_id_tenant_uk');
            $table->foreign(['organization_unit_id', 'tenant_id'], 'invoices_organization_unit_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('organization_units')
                ->restrictOnDelete();

            $table->foreign(['created_by', 'tenant_id'], 'invoices_created_by_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('users')
                ->restrictOnDelete();
            $table->foreign(['approved_by', 'tenant_id'], 'invoices_approved_by_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('users')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
