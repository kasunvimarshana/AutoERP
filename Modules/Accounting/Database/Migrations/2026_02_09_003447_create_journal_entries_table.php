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
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->uuid('uuid')->unique();
            $table->string('entry_number', 50)->unique();
            $table->date('entry_date');
            $table->string('reference')->nullable();
            $table->text('description');
            $table->foreignId('fiscal_period_id')->constrained('fiscal_periods');
            $table->enum('status', ['draft', 'posted', 'reversed'])->default('draft');
            $table->decimal('total_debit', 15, 2)->default(0);
            $table->decimal('total_credit', 15, 2)->default(0);
            $table->char('currency', 3)->default('USD');
            $table->timestamp('posted_at')->nullable();
            $table->foreignId('posted_by')->nullable();
            $table->foreignId('reversed_entry_id')->nullable()->constrained('journal_entries');
            $table->json('tags')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'entry_date']);
            $table->index('fiscal_period_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('journal_entries');
    }
};
