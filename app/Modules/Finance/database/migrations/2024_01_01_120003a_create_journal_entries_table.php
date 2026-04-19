<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('journal_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained(null, 'id', 'journal_entries_tenant_id_fk')->cascadeOnDelete();
            $table->foreignId('fiscal_period_id')->constrained(null, 'id', 'journal_entries_fiscal_period_id_fk')->cascadeOnDelete();
            $table->string('entry_number')->nullable();
            $table->enum('entry_type', ['manual', 'auto', 'system'])->default('manual');
            $table->nullableMorphs('reference');
            $table->text('description')->nullable();
            $table->date('entry_date');
            $table->date('posting_date')->nullable();
            $table->enum('status', ['draft', 'posted', 'reversed'])->default('draft');
            $table->boolean('is_reversed')->default(false);
            $table->foreignId('reversal_entry_id')->nullable()->constrained('journal_entries', 'id', 'journal_entries_reversal_entry_id_fk')->nullOnDelete();
            $table->foreignId('created_by');
            $table->foreignId('posted_by')->nullable();
            $table->timestamp('posted_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'entry_number'], 'journal_entries_tenant_number_uk');
            $table->index(['tenant_id', 'fiscal_period_id', 'status'], 'journal_entries_tenant_period_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('journal_entries');
    }
};
