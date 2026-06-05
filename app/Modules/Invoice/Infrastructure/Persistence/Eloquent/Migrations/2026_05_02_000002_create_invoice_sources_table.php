<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoice_sources', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('invoice_id')->constrained('invoices')->cascadeOnDelete();

            $table->string('source_module', 100);
            // purchase, sales, vehicle_service, manual

            $table->string('source_type', 100);
            // purchase_order, grn, sales_order, gdn, service_job, manual

            $table->unsignedBigInteger('source_id');
            $table->string('source_number', 100)->nullable();
            $table->date('source_date')->nullable();

            $table->decimal('source_total', 20, 4)->default(0);
            $table->decimal('already_invoiced_total', 20, 4)->default(0);
            $table->decimal('current_invoiced_total', 20, 4)->default(0);
            $table->decimal('remaining_total', 20, 4)->default(0);

            $table->string('status', 50)->default('not_invoiced');
            // not_invoiced, partially_invoiced, fully_invoiced

            $table->timestamps();

            $table->index(['invoice_id']);
            $table->index(['source_module', 'source_type', 'source_id']);
            $table->index(['source_type', 'source_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_sources');
    }
};
