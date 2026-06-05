<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoice_relations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('source_invoice_id')->constrained('invoices')->cascadeOnDelete();
            $table->foreignId('target_invoice_id')->constrained('invoices')->cascadeOnDelete();
            $table->string('relation_type', 50);
            // adjusts, credits, debits, refunds, reverses, replaces
            $table->decimal('applied_amount', 20, 4)->default(0);
            $table->string('status', 30)->default('active');
            $table->date('effective_date')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(
                ['source_invoice_id', 'target_invoice_id', 'relation_type'],
                'invoice_relations_source_target_type_uk',
            );
            $table->index(['target_invoice_id', 'relation_type', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_relations');
    }
};
