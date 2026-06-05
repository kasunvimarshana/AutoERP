<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoice_source_lines', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('invoice_source_id')->constrained('invoice_sources')->cascadeOnDelete();

            $table->string('source_line_type', 100);
            // purchase_order_line, grn_line, service_job_line

            $table->unsignedBigInteger('source_line_id');
            $table->string('source_line_number', 100)->nullable();

            $table->decimal('source_quantity', 20, 4)->default(0);
            $table->decimal('already_invoiced_quantity', 20, 4)->default(0);
            $table->decimal('current_invoiced_quantity', 20, 4)->default(0);
            $table->decimal('remaining_quantity', 20, 4)->default(0);

            $table->decimal('source_amount', 20, 4)->default(0);
            $table->decimal('already_invoiced_amount', 20, 4)->default(0);
            $table->decimal('current_invoiced_amount', 20, 4)->default(0);
            $table->decimal('remaining_amount', 20, 4)->default(0);

            $table->string('status', 50)->default('not_invoiced');
            // not_invoiced, partially_invoiced, fully_invoiced

            $table->timestamps();

            $table->index(['invoice_source_id']);
            $table->index(['source_line_type', 'source_line_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_source_lines');
    }
};
