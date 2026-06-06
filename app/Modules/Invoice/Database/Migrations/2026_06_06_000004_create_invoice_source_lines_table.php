<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoice_source_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units', 'id')->nullOnDelete();
            $table->foreignId('invoice_id')->constrained('invoices', 'id')->cascadeOnDelete();
            $table->foreignId('invoice_line_id')->nullable()->constrained('invoice_lines', 'id')->nullOnDelete();
            $table->string('source_type');
            $table->unsignedBigInteger('source_id');
            $table->string('source_line_type');
            $table->unsignedBigInteger('source_line_id');
            $table->decimal('source_quantity', 20, 6)->default('0');
            $table->decimal('previously_invoiced_quantity', 20, 6)->default('0');
            $table->decimal('invoiced_quantity', 20, 6)->default('0');
            $table->decimal('remaining_quantity', 20, 6)->default('0');
            $table->decimal('source_unit_price', 20, 6)->default('0');
            $table->decimal('source_line_total', 20, 6)->default('0');
            $table->decimal('invoiced_line_total', 20, 6)->default('0');
            $table->timestamps();

            $table->index(['source_type', 'source_id'], 'invoice_source_lines_source_idx');
            $table->index(['source_line_type', 'source_line_id'], 'invoice_source_lines_line_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_source_lines');
    }
};
