<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_job_card_lines', function (Blueprint $table) {
            $table->id();

            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('org_unit_id')->nullable()->constrained('org_units', 'id')->nullOnDelete();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');

            $table->foreignId('job_card_id')->constrained('service_job_cards')->cascadeOnDelete();
            $table->integer('line_number');
            $table->enum('line_type', ['labor','part','non_inventory','fee'])->default('part');
            $table->string('description')->nullable();
            $table->decimal('quantity', 12, 2)->default(1);
            $table->decimal('unit_price', 20, 6);
            $table->decimal('amount', 20, 6);
            $table->foreignId('tax_rate_id')->nullable()->constrained('tax_rates')->nullOnDelete();
            $table->decimal('tax_amount', 20, 6)->default(0);
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->foreignId('variant_id')->nullable()->constrained('product_variants')->nullOnDelete();
            $table->boolean('is_warranty_replacement')->default(false);
            $table->foreignId('inventory_movement_id')->nullable()->constrained('stock_movements')->nullOnDelete();
            $table->foreignId('technician_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->decimal('hours_worked', 8, 2)->nullable();
            $table->string('labor_type')->nullable();
            $table->decimal('commission_percentage', 7, 4)->nullable();
            $table->decimal('commission_amount', 20, 6)->nullable();
            $table->decimal('incentive_amount', 20, 6)->nullable();
            $table->foreignId('purchase_order_id')->nullable()->constrained('purchase_orders')->nullOnDelete();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->unique(['tenant_id','job_card_id','line_number'], 'job_card_lines_number_uk');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_job_card_lines');
    }
};
