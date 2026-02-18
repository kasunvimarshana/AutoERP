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
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->uuid('journal_entry_id');
            $table->uuid('account_id');

            $table->foreign('journal_entry_id')->references('id')->on('journal_entries')->cascadeOnDelete();
            $table->foreign('account_id')->references('id')->on('accounts')->restrictOnDelete();

            $table->decimal('debit_amount', 19, 4)->default(0);
            $table->decimal('credit_amount', 19, 4)->default(0);
            $table->text('description')->nullable();

            $table->timestamps();

            $table->index(['journal_entry_id']);
            $table->index(['account_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('journal_entry_lines');
    }
};
