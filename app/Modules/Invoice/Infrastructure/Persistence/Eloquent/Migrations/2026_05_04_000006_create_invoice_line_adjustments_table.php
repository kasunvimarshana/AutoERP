<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoice_line_adjustments', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('invoice_line_id')->constrained('invoice_lines')->cascadeOnDelete();
            $table->string('direction', 20);
            $table->string('adjustment_type', 50);
            $table->string('code', 100)->nullable();
            $table->string('name', 150)->nullable();
            $table->string('calculation_method', 50)->default('fixed');
            $table->decimal('rate', 20, 6)->nullable();
            $table->decimal('base_amount', 20, 4)->default(0);
            $table->decimal('amount', 20, 4)->default(0);
            $table->unsignedInteger('sort_order')->default(0);

            $table->timestamps();

            $table->index(['invoice_line_id', 'adjustment_type'], 'invoice_line_adjustments_line_type_idx');
            $table->index(['invoice_line_id', 'direction'], 'invoice_line_adjustments_line_direction_idx');
            $table->index(['code'], 'invoice_line_adjustments_code_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_line_adjustments');
    }
};
