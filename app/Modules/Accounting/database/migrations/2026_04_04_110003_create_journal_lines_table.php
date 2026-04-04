<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('journal_lines', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('journal_entry_id')->index();
            $table->unsignedBigInteger('account_id')->index();
            $table->decimal('debit', 18, 4)->default(0);
            $table->decimal('credit', 18, 4)->default(0);
            $table->string('currency', 10)->default('USD');
            $table->text('description')->nullable();
            $table->timestamps();

            $table->foreign('journal_entry_id')
                ->references('id')->on('journal_entries')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('journal_lines');
    }
};
