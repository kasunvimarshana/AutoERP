<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('accounting_journal_lines', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('journal_entry_id')->index();
            $table->uuid('account_id')->index();
            $table->decimal('debit', 18, 8)->default(0);
            $table->decimal('credit', 18, 8)->default(0);
            $table->text('description')->nullable();
            $table->timestamps();

            $table->foreign('journal_entry_id')
                  ->references('id')
                  ->on('accounting_journal_entries')
                  ->onDelete('cascade');

            $table->foreign('account_id')
                  ->references('id')
                  ->on('accounting_accounts')
                  ->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounting_journal_lines');
    }
};
