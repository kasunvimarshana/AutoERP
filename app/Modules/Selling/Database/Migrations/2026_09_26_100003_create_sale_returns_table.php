<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sale_returns', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants', indexName: 'sale_returns_tenant_fk')->restrictOnDelete();
            $table->foreignId('organization_unit_id')->nullable();
            $table->foreignId('sale_id');
            $table->foreignId('invoice_id');
            $table->string('return_number', 100);
            $table->date('return_date');
            $table->text('reason');
            $table->decimal('credit_amount', 20, 6)->default('0.000000');
            $table->decimal('credit_allocated_amount', 20, 6)->default('0.000000');
            $table->decimal('credit_available_amount', 20, 6)->default('0.000000');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamp('posted_at')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'return_number'], 'sale_returns_tenant_number_uk');
            $table->index(['tenant_id', 'sale_id', 'return_date'], 'sale_returns_sale_date_ix');
            $table->unique(['id', 'tenant_id'], 'sale_returns_id_tenant_uk');
            $table->foreign(['organization_unit_id', 'tenant_id'], 'sale_returns_org_tenant_fk')
                ->references(['id', 'tenant_id'])->on('organization_units')->restrictOnDelete();
            $table->foreign(['sale_id', 'tenant_id'], 'sale_returns_sale_tenant_fk')
                ->references(['id', 'tenant_id'])->on('sales')->restrictOnDelete();
            $table->foreign(['invoice_id', 'tenant_id'], 'sale_returns_invoice_tenant_fk')
                ->references(['id', 'tenant_id'])->on('invoices')->restrictOnDelete();
            $table->foreign(['created_by', 'tenant_id'], 'sale_returns_created_by_tenant_fk')
                ->references(['id', 'tenant_id'])->on('users')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_returns');
    }
};
