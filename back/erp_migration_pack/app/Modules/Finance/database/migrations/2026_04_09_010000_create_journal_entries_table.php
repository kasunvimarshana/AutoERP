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
            $table->foreignId('fiscal_period_id')->constrained('fiscal_periods')->cascadeOnDelete();
            $table->foreignId('currency_id')->constrained('currencies')->cascadeOnDelete();
            $table->string('entry_number');
            $table->string('entry_type')->default("general");
            $table->string('source_type')->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->string('source_line_type')->nullable();
            $table->unsignedBigInteger('source_line_id')->nullable();
            $table->string('status')->default("draft");
            $table->date('entry_date');
            $table->date('posting_date');
            $table->timestamp('posted_at')->nullable();
            $table->foreignId('posted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('memo')->nullable();
            $table->json('metadata')->nullable();
            $table->unique(['tenant_id', 'entry_number']);
            $table->index(['tenant_id', 'fiscal_period_id']);
            $table->index(['tenant_id', 'entry_date']);
            $table->index(['tenant_id', 'source_type', 'source_id']);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('journal_entries');
    }
};
