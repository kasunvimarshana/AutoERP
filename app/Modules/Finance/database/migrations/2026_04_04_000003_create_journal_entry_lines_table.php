<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('journal_entry_lines', static function (Blueprint $table): void {
            $table->id();
            $table->foreignId('journal_entry_id')
                  ->constrained('journal_entries')
                  ->cascadeOnDelete();
            $table->foreignId('account_id')
                  ->constrained('accounts')
                  ->restrictOnDelete();
            $table->text('description')->nullable();
            $table->decimal('debit_amount', 20, 6)->default(0.000000);
            $table->decimal('credit_amount', 20, 6)->default(0.000000);
            $table->string('currency', 3)->default('USD');
            $table->decimal('exchange_rate', 15, 8)->default(1.00000000);
            $table->integer('sort_order')->default(0);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['journal_entry_id', 'sort_order']);
            $table->index('account_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('journal_entry_lines');
    }
};
