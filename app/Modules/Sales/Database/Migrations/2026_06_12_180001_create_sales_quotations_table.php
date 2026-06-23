<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_quotations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable();
            $table->string('quotation_number');
            $table->date('quotation_date');
            $table->date('valid_until')->nullable();
            $table->foreignId('customer_id');
            $table->unsignedBigInteger('currency_id')->nullable();
            $table->decimal('exchange_rate', 20, 6)->default('1.000000');
            $table->string('status')->default('draft');
            $table->decimal('subtotal', 20, 6)->default('0.000000');
            $table->decimal('line_discount_total', 20, 6)->default('0.000000');
            $table->decimal('line_tax_total', 20, 6)->default('0.000000');
            $table->decimal('line_charge_total', 20, 6)->default('0.000000');
            $table->decimal('header_increase_total', 20, 6)->default('0.000000');
            $table->decimal('header_decrease_total', 20, 6)->default('0.000000');
            $table->decimal('grand_total', 20, 6)->default('0.000000');
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'quotation_number'], 'sales_quotations_tenant_number_uk');
            $table->index(['tenant_id', 'organization_unit_id'], 'sales_quotations_scope_idx');
            $table->index(['customer_id', 'status'], 'sales_quotations_customer_status_idx');

            $table->unique(['id', 'tenant_id'], 'sales_quotations_id_tenant_uk');
            $table->foreign(['organization_unit_id', 'tenant_id'], 'sales_quotations_organization_unit_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('organization_units')
                ->restrictOnDelete();
            $table->foreign(['customer_id', 'tenant_id'], 'sales_quotations_customer_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('customers')
                ->restrictOnDelete();

            $table->foreign(['created_by', 'tenant_id'], 'sales_quotations_created_by_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('users')
                ->restrictOnDelete();
            $table->foreign(['approved_by', 'tenant_id'], 'sales_quotations_approved_by_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('users')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_quotations');
    }
};
