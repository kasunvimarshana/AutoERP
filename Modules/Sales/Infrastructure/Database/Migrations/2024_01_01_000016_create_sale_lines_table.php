<?php
declare(strict_types=1);
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('sale_lines', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('sale_id')->constrained('sales')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('variant_id')->nullable()->constrained('product_variants')->nullOnDelete();
            $table->decimal('quantity', 15, 4);
            $table->decimal('unit_price', 15, 4);
            $table->decimal('discount_percent', 8, 4)->default(0);
            $table->decimal('tax_percent', 8, 4)->default(0);
            $table->decimal('line_total', 15, 4);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['tenant_id', 'sale_id']);
        });
    }
    public function down(): void { Schema::dropIfExists('sale_lines'); }
};
