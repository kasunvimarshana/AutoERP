<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_returns', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units')->nullOnDelete();
            $table->string('supplier_type')->nullable();
            $table->unsignedBigInteger('supplier_id')->nullable();
            $table->foreignId('warehouse_id')->constrained('warehouses')->restrictOnDelete();
            $table->foreignId('warehouse_location_id')->nullable()->constrained('warehouse_locations')->nullOnDelete();
            $table->string('return_number');
            $table->string('return_type')->default('referenced');
            $table->string('source_type')->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->date('return_date');
            $table->string('status')->default('draft');
            $table->text('reason')->nullable();
            $table->boolean('approval_required')->default(false);
            $table->boolean('affects_supplier_balance')->default(true);
            $table->decimal('cost_basis', 20, 6)->nullable();
            $table->json('audit_metadata')->nullable();
            $table->decimal('subtotal', 20, 6)->default('0.000000');
            $table->decimal('adjustment_return_total', 20, 6)->default('0.000000');
            $table->decimal('grand_total', 20, 6)->default('0.000000');
            $table->unsignedBigInteger('debit_note_id')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->unsignedBigInteger('posted_by')->nullable();
            $table->timestamp('posted_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'return_number'], 'purchase_returns_tenant_number_uk');
            $table->index(['tenant_id', 'organization_unit_id'], 'purchase_returns_tenant_org_idx');
            $table->index(['supplier_type', 'supplier_id'], 'purchase_returns_supplier_idx');
            $table->index('warehouse_id', 'purchase_returns_warehouse_idx');
            $table->index('status', 'purchase_returns_status_idx');
            $table->index('return_date', 'purchase_returns_date_idx');
            $table->index(['source_type', 'source_id'], 'purchase_returns_source_idx');
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_returns');
    }
};
