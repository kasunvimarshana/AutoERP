<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('journal_lines', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('journal_entry_id');
            $table->unsignedBigInteger('account_id');
            $table->decimal('debit', 18, 4)->default(0);
            $table->decimal('credit', 18, 4)->default(0);
            $table->decimal('base_debit', 18, 4)->default(0);
            $table->decimal('base_credit', 18, 4)->default(0);
            $table->unsignedBigInteger('party_id')->nullable();
            $table->unsignedBigInteger('cost_center_id')->nullable();
            $table->text('description')->nullable();
            $table->integer('line_number');
            $table->timestamps();

            $table->foreign('journal_entry_id')->references('id')->on('journal_entries')->cascadeOnDelete();
            $table->foreign('account_id')->references('id')->on('chart_of_accounts')->cascadeOnDelete();
            $table->foreign('party_id')->references('id')->on('parties')->nullOnDelete();
            $table->foreign('cost_center_id')->references('id')->on('cost_centers')->nullOnDelete();

            $table->index(['journal_entry_id', 'line_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('journal_lines');
    }
};