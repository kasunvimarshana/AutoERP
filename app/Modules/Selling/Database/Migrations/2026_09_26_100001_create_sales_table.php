<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1);
            $table->foreignId('tenant_id')->constrained('tenants', indexName: 'sales_tenant_fk')->restrictOnDelete();
            $table->foreignId('organization_unit_id')->nullable();
            $table->foreignId('customer_id');
            $table->foreignId('warehouse_id');
            $table->foreignId('warehouse_location_id')->nullable();
            $table->foreignId('invoice_id')->nullable();
            $table->string('sale_number', 100);
            $table->date('sale_date');
            $table->date('due_date')->nullable();
            $table->string('status', 30)->default('draft');
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamp('posted_at')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'sale_number'], 'sales_tenant_number_uk');
            $table->unique(['invoice_id'], 'sales_invoice_uk');
            $table->index(['tenant_id', 'organization_unit_id', 'sale_date'], 'sales_scope_date_ix');
            $table->unique(['id', 'tenant_id'], 'sales_id_tenant_uk');
            $table->foreign(['organization_unit_id', 'tenant_id'], 'sales_org_tenant_fk')
                ->references(['id', 'tenant_id'])->on('organization_units')->restrictOnDelete();
            $table->foreign(['customer_id', 'tenant_id'], 'sales_customer_tenant_fk')
                ->references(['id', 'tenant_id'])->on('customers')->restrictOnDelete();
            $table->foreign(['warehouse_id', 'tenant_id'], 'sales_warehouse_tenant_fk')
                ->references(['id', 'tenant_id'])->on('warehouses')->restrictOnDelete();
            $table->foreign(['warehouse_location_id', 'tenant_id'], 'sales_location_tenant_fk')
                ->references(['id', 'tenant_id'])->on('warehouse_locations')->restrictOnDelete();
            $table->foreign(['invoice_id', 'tenant_id'], 'sales_invoice_tenant_fk')
                ->references(['id', 'tenant_id'])->on('invoices')->restrictOnDelete();
            $table->foreign(['created_by', 'tenant_id'], 'sales_created_by_tenant_fk')
                ->references(['id', 'tenant_id'])->on('users')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales');
    }
};
