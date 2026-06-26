<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_credit_notes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants', indexName: 'sales_credit_notes_tenant_fk')->restrictOnDelete();
            $table->foreignId('organization_unit_id')->nullable();
            $table->foreignId('customer_id');
            $table->foreignId('sales_return_id')->nullable();
            $table->string('credit_note_number');
            $table->date('credit_note_date');
            $table->string('status')->default('draft');
            $table->decimal('amount', 20, 6);
            $table->decimal('allocated_amount', 20, 6)->default('0.000000');
            $table->decimal('remaining_amount', 20, 6);
            $table->text('reason')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'credit_note_number'], 'sales_credit_notes_tenant_number_uk');
            $table->index(['tenant_id', 'organization_unit_id'], 'sales_credit_notes_scope_ix');

            $table->unique(['id', 'tenant_id'], 'sales_credit_notes_id_tenant_uk');
            $table->foreign(['organization_unit_id', 'tenant_id'], 'sales_credit_notes_organization_unit_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('organization_units')
                ->restrictOnDelete();
            $table->foreign(['customer_id', 'tenant_id'], 'sales_credit_notes_customer_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('customers')
                ->restrictOnDelete();
            $table->foreign(['sales_return_id', 'tenant_id'], 'sales_credit_notes_sales_return_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('sales_returns')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_credit_notes');
    }
};
