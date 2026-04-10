<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('journal_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('period_id')->constrained('accounting_periods')->restrictOnDelete();
            $table->string('entry_number', 50);
            $table->date('entry_date');
            $table->date('post_date');
            $table->string('source_type', 100);
            $table->unsignedBigInteger('source_id')->nullable();
            $table->string('reference', 100)->nullable();
            $table->text('description');
            $table->foreignId('currency_id')->constrained('currencies')->restrictOnDelete();
            $table->decimal('exchange_rate', 20, 8);
            $table->enum('status', ['draft', 'posted', 'reversed'])->default('draft');
            $table->foreignId('reversed_by')->nullable()->constrained('journal_entries')->nullOnDelete();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('posted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('posted_at')->nullable();
            $table->unique(['tenant_id', 'entry_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('journal_entries');
    }
};
