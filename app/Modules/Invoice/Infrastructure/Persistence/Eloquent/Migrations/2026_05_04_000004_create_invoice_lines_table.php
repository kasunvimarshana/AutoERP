<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoice_lines', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('invoice_id')->constrained('invoices')->cascadeOnDelete();

            $table->foreignId('invoice_source_line_id')
                ->nullable()
                ->constrained('invoice_source_lines')
                ->nullOnDelete();

            $table->unsignedInteger('line_no')->default(1);

            $table->string('line_type', 50)->default('item');
            // item, service, charge, discount, tax, adjustment, note

            $table->foreignId('item_id')->nullable()->constrained('items')->nullOnDelete();
            $table->foreignId('uom_id')->nullable()->constrained('unit_of_measures')->nullOnDelete();

            // Snapshot fields - important
            $table->string('item_code', 100)->nullable();
            $table->string('item_name', 255)->nullable();
            $table->string('uom_code', 50)->nullable();
            $table->text('description')->nullable();

            $table->decimal('quantity', 20, 4)->default(1);
            $table->decimal('unit_price', 20, 4)->default(0);

            $table->decimal('gross_amount', 20, 4)->default(0);
            $table->decimal('discount_total', 20, 4)->default(0);
            $table->decimal('taxable_amount', 20, 4)->default(0);
            $table->decimal('tax_total', 20, 4)->default(0);
            $table->decimal('charge_total', 20, 4)->default(0);
            $table->decimal('net_amount', 20, 4)->default(0);

            $table->timestamps();

            $table->index(['invoice_id', 'line_no']);
            $table->index(['invoice_source_line_id']);
            $table->index(['item_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_lines');
    }
};
