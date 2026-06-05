<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoice_accounting_entries', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('invoice_id')->constrained('invoices')->cascadeOnDelete();

            $table->foreignId('journal_entry_id')
                ->nullable()
                ->constrained('journal_entries')
                ->nullOnDelete();

            $table->string('posting_status', 50)->default('pending');
            // pending, posted, reversed, failed

            $table->text('posting_error')->nullable();

            $table->timestamp('posted_at')->nullable();
            $table->timestamp('reversed_at')->nullable();

            $table->timestamps();

            $table->index(['invoice_id']);
            $table->index(['journal_entry_id']);
            $table->index(['posting_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_accounting_entries');
    }
};
