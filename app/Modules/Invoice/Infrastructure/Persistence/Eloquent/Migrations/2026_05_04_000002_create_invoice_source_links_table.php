<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoice_source_links', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('invoice_id')->constrained('invoices')->cascadeOnDelete();
            $table->string('source_type', 100);
            $table->unsignedBigInteger('source_id');
            $table->string('source_number', 100)->nullable();
            $table->date('source_date')->nullable();

            $table->timestamps();

            $table->index(['invoice_id'], 'invoice_source_links_invoice_idx');
            $table->index(['source_type', 'source_id'], 'invoice_source_links_source_idx');
            $table->unique(['invoice_id', 'source_type', 'source_id'], 'invoice_source_links_invoice_source_uk');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_source_links');
    }
};
