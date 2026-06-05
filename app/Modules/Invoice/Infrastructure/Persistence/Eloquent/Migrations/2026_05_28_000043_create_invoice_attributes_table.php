<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoice_attributes', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('invoice_id')->constrained('invoices')->cascadeOnDelete();

            $table->string('attribute_key', 150);
            $table->text('attribute_value')->nullable();

            $table->string('value_type', 50)->default('string');
            // string, number, date, boolean

            $table->timestamps();

            $table->unique(['invoice_id', 'attribute_key']);
            $table->index(['attribute_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_attributes');
    }
};
