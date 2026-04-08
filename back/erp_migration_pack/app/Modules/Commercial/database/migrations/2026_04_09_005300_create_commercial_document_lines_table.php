<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commercial_document_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('commercial_document_id')->constrained('commercial_documents')->cascadeOnDelete();
            $table->foreignId('product_variant_id')->nullable()->constrained('product_variants')->nullOnDelete();
            $table->foreignId('unit_of_measure_id')->constrained('units_of_measure')->cascadeOnDelete();
            $table->foreignId('tax_category_id')->nullable()->constrained('tax_categories')->nullOnDelete();
            $table->foreignId('source_line_id')->nullable()->constrained('commercial_document_lines')->nullOnDelete();
            $table->string('line_type')->default("item");
            $table->unsignedInteger('line_number');
            $table->text('description')->nullable();
            $table->decimal('quantity', 24, 8);
            $table->decimal('quantity_fulfilled', 24, 8)->default(0);
            $table->decimal('unit_price', 19, 4)->default(0);
            $table->decimal('unit_cost', 19, 4)->nullable();
            $table->decimal('discount_amount', 19, 4)->default(0);
            $table->decimal('tax_amount', 19, 4)->default(0);
            $table->decimal('line_subtotal', 19, 4)->default(0);
            $table->decimal('line_total', 19, 4)->default(0);
            $table->json('metadata')->nullable();
            $table->unique(['commercial_document_id', 'line_number']);
            $table->index(['tenant_id', 'product_variant_id']);
            $table->index(['commercial_document_id', 'product_variant_id']);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commercial_document_lines');
    }
};
