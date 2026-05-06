<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_non_inventory_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->unsignedBigInteger('row_version')->default(1);

            $table->foreignId('job_card_id')->constrained('service_job_cards')->cascadeOnDelete();
            $table->string('item_name');
            $table->text('item_description')->nullable();
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->nullOnDelete();
            $table->decimal('quantity', 20, 6);
            $table->decimal('unit_cost', 20, 6);
            $table->decimal('total_cost', 20, 6);
            $table->boolean('is_billable')->default(true);
            $table->foreignId('purchase_order_id')->nullable()->constrained('purchase_orders')->nullOnDelete();
            $table->foreignId('tax_rate_id')->nullable()->constrained('tax_rates')->nullOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_non_inventory_items');
    }
};
