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
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units')->nullOnDelete();
            $table->string('supplier_type')->nullable();
            $table->unsignedBigInteger('supplier_id')->nullable();
            $table->foreignId('purchase_return_id')->nullable()->constrained('purchase_returns')->nullOnDelete();
            $table->string('debit_note_number');
            $table->date('debit_note_date');
            $table->string('status')->default('draft');
            $table->decimal('amount', 20, 6);
            $table->decimal('allocated_amount', 20, 6)->default('0.000000');
            $table->decimal('remaining_amount', 20, 6);
            $table->text('reason')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'debit_note_number'], 'purchase_debit_notes_tenant_number_uk');
            $table->index(['tenant_id', 'organization_unit_id'], 'purchase_debit_notes_tenant_org_idx');
            $table->index(['supplier_type', 'supplier_id'], 'purchase_debit_notes_supplier_idx');
            $table->index('purchase_return_id', 'purchase_debit_notes_return_idx');
            $table->index('status', 'purchase_debit_notes_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_debit_notes');
    }
};
