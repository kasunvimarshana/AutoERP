<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('journal_entry_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('journal_entry_id')->constrained()->onDelete('cascade');
            $table->foreignId('account_id')->constrained()->onDelete('restrict');
            $table->decimal('debit', 15, 2)->default(0.00);
            $table->decimal('credit', 15, 2)->default(0.00);
            $table->text('description')->nullable();
            $table->foreignId('cost_center_id')->nullable()->constrained()->onDelete('restrict');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'journal_entry_id']);
            $table->index(['journal_entry_id', 'account_id']);
            $table->index('account_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('journal_entry_lines');
    }
};
