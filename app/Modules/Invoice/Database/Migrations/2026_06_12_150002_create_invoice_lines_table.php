<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoice_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units', 'id')->nullOnDelete();
            $table->foreignId('invoice_id')->constrained('invoices', 'id')->cascadeOnDelete();
            $table->unsignedInteger('line_number');
            $table->unsignedBigInteger('item_id')->nullable();
            $table->text('description');
            $table->enum('line_type', ['item', 'service', 'labour', 'charge', 'discount', 'tax', 'rounding', 'manual'])->default('item');
            $table->decimal('quantity', 20, 6)->default('0');
            $table->foreignId('uom_id')->nullable()->constrained('unit_of_measures', 'id')->nullOnDelete();
            $table->decimal('unit_price', 20, 6)->default('0');
            $table->decimal('discount_amount', 20, 6)->default('0');
            $table->decimal('tax_amount', 20, 6)->default('0');
            $table->decimal('charge_amount', 20, 6)->default('0');
            $table->decimal('line_total', 20, 6)->default('0');
            $table->string('source_line_type')->nullable();
            $table->unsignedBigInteger('source_line_id')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index('invoice_id', 'invoice_lines_invoice_idx');
            $table->index('item_id', 'invoice_lines_item_idx');
            $table->index(['source_line_type', 'source_line_id'], 'invoice_lines_source_line_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_lines');
    }
};
