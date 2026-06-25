<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_debit_notes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->restrictOnDelete();
            $table->foreignId('organization_unit_id')->nullable();
            $table->string('supplier_type')->nullable();
            $table->unsignedBigInteger('supplier_id')->nullable();
            $table->foreignId('purchase_return_id')->nullable();
            $table->string('source_type')->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->string('debit_note_number');
            $table->date('debit_note_date');
            $table->string('status')->default('draft');
            $table->decimal('amount', 20, 6);
            $table->decimal('allocated_amount', 20, 6)->default('0.000000');
            $table->decimal('remaining_amount', 20, 6);
            $table->text('reason')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'debit_note_number'], 'purchase_debit_notes_tenant_number_uk');
            $table->index(['tenant_id', 'organization_unit_id'], 'purchase_debit_notes_tenant_org_idx');
            $table->index(['supplier_type', 'supplier_id'], 'purchase_debit_notes_supplier_idx');
            $table->index('purchase_return_id', 'purchase_debit_notes_return_idx');
            $table->index('status', 'purchase_debit_notes_status_idx');
            $table->index(['source_type', 'source_id'], 'purchase_debit_notes_source_idx');
            $table->index(['status', 'allocated_amount', 'remaining_amount'], 'purchase_debit_notes_allocation_idx');

            $table->unique(['id', 'tenant_id'], 'purchase_debit_notes_id_tenant_uk');
            $table->foreign(['organization_unit_id', 'tenant_id'], 'purchase_debit_notes_organization_unit_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('organization_units')
                ->restrictOnDelete();
            $table->foreign(['purchase_return_id', 'tenant_id'], 'purchase_debit_notes_purchase_return_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('purchase_returns')
                ->restrictOnDelete();

            $table->foreign(['approved_by', 'tenant_id'], 'purchase_debit_notes_approved_by_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('users')
                ->restrictOnDelete();
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_debit_notes');
    }
};
