<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_order_lines', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units')->nullOnDelete();
            $table->foreignId('purchase_order_id')->constrained('purchase_orders')->cascadeOnDelete();
            $table->unsignedInteger('line_number');
            $table->foreignId('item_id')->constrained('items')->restrictOnDelete();
            $table->foreignId('item_variant_id')->nullable()->constrained('item_variants')->nullOnDelete();
            $table->text('description')->nullable();
            $table->foreignId('uom_id')->nullable()->constrained('unit_of_measures')->nullOnDelete();
            $table->decimal('ordered_quantity', 20, 6);
            $table->decimal('received_quantity', 20, 6)->default('0.000000');
            $table->decimal('invoiced_quantity', 20, 6)->default('0.000000');
            $table->decimal('returned_quantity', 20, 6)->default('0.000000');
            $table->decimal('cancelled_quantity', 20, 6)->default('0.000000');
            $table->decimal('remaining_quantity', 20, 6);
            $table->decimal('unit_price', 20, 6);
            $table->decimal('discount_amount', 20, 6)->default('0.000000');
            $table->decimal('tax_amount', 20, 6)->default('0.000000');
            $table->decimal('charge_amount', 20, 6)->default('0.000000');
            $table->decimal('line_total', 20, 6);
            $table->string('status')->default('open');
            $table->timestamps();

            $table->index('purchase_order_id', 'purchase_order_lines_order_idx');
            $table->index('item_id', 'purchase_order_lines_item_idx');
            $table->index('status', 'purchase_order_lines_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_order_lines');
    }
};
