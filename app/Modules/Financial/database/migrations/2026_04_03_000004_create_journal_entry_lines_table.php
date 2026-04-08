<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('journal_entry_lines', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->uuid('journal_entry_id');
            $table->uuid('account_id');
            $table->text('description')->nullable();
            $table->decimal('debit', 15, 4)->default(0);
            $table->decimal('credit', 15, 4)->default(0);
            $table->string('currency_code', 10)->default('USD');
            $table->decimal('exchange_rate', 15, 6)->default(1);
            $table->string('reference', 100)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['journal_entry_id']);
            $table->index(['account_id']);

            $table->foreign('journal_entry_id')->references('id')->on('journal_entries')->cascadeOnDelete();
            $table->foreign('account_id')->references('id')->on('accounts');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('journal_entry_lines');
    }
};
