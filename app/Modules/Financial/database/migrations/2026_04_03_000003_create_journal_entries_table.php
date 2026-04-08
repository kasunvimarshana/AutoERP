<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('journal_entries', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->uuid('fiscal_year_id')->nullable();
            $table->string('entry_number', 50);
            $table->date('entry_date');
            $table->date('posting_date')->nullable();
            // manual, auto, adjustment, closing
            $table->string('type', 30)->default('manual');
            // draft, posted, voided
            $table->string('status', 30)->default('draft');
            $table->text('description')->nullable();
            $table->string('reference', 100)->nullable();
            $table->string('currency_code', 10)->default('USD');
            $table->decimal('exchange_rate', 15, 6)->default(1);
            $table->decimal('total_debit', 15, 4)->default(0);
            $table->decimal('total_credit', 15, 4)->default(0);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('posted_by')->nullable();
            $table->timestamp('posted_at')->nullable();
            $table->unsignedBigInteger('voided_by')->nullable();
            $table->timestamp('voided_at')->nullable();
            $table->text('void_reason')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'entry_number']);
            $table->index(['tenant_id', 'entry_date']);
            $table->index(['tenant_id', 'status']);

            $table->foreign('fiscal_year_id')->references('id')->on('fiscal_years')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('journal_entries');
    }
};
