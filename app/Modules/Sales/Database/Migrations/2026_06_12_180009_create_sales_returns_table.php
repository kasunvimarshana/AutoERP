<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_returns', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units')->nullOnDelete();
            $table->string('return_number');
            $table->date('return_date');
            $table->foreignId('customer_id')->constrained('customers')->restrictOnDelete();
            $table->foreignId('warehouse_id')->nullable()->constrained('warehouses')->nullOnDelete();
            $table->foreignId('warehouse_location_id')->nullable()->constrained('warehouse_locations')->nullOnDelete();
            $table->string('return_type');
            $table->string('status')->default('draft');
            $table->text('reason')->nullable();
            $table->decimal('subtotal', 20, 6)->default('0.000000');
            $table->decimal('adjustment_return_total', 20, 6)->default('0.000000');
            $table->decimal('grand_total', 20, 6)->default('0.000000');
            $table->unsignedBigInteger('credit_note_id')->nullable();
            $table->foreignId('replacement_sales_order_id')->nullable()->constrained('sales_orders')->nullOnDelete();
            $table->boolean('affects_inventory')->default(true);
            $table->boolean('affects_customer_balance')->default(true);
            $table->boolean('approval_required')->default(false);
            $table->decimal('cost_basis', 20, 6)->nullable();
            $table->json('audit_metadata')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->unsignedBigInteger('posted_by')->nullable();
            $table->timestamp('posted_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'return_number'], 'sales_returns_tenant_number_uk');
            $table->index(['tenant_id', 'organization_unit_id'], 'sales_returns_scope_idx');
            $table->index(['customer_id', 'status'], 'sales_returns_customer_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_returns');
    }
};
