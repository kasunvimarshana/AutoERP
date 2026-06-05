<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoice_adjustments', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('invoice_id')->constrained('invoices')->cascadeOnDelete();

            $table->foreignId('invoice_line_id')
                ->nullable()
                ->constrained('invoice_lines')
                ->cascadeOnDelete();

            $table->string('level', 30);
            // header, line

            $table->string('effect', 20); // add, subtract

            $table->string('adjustment_type', 50);
            // tax, discount, charge, withholding, rounding

            $table->string('code', 100)->nullable();
            // VAT, SVAT, SERVICE_CHARGE, CASH_DISCOUNT

            $table->string('name', 150)->nullable();

            $table->string('calculation_method', 50)->default('fixed');
            // fixed, percentage, formula

            $table->decimal('rate', 20, 6)->nullable();
            $table->decimal('base_amount', 20, 4)->default(0);
            $table->decimal('amount', 20, 4)->default(0);

            $table->boolean('is_inclusive')->default(false);
            $table->boolean('is_compound')->default(false);

            $table->unsignedInteger('sort_order')->default(0);

            $table->timestamps();

            $table->index(['invoice_id', 'level']);
            $table->index(['invoice_id', 'adjustment_type']);
            $table->index(['invoice_line_id']);
            $table->index(['code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_adjustments');
    }
};
