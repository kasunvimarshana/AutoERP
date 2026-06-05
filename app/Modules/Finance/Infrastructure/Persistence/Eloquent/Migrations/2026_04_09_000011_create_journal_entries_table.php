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
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete()->comment('Multi-tenant owner reference');
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units', 'id')->nullOnDelete()->comment('Branch or department ownership');
            $table->json('metadata')->nullable()->comment('Extensible custom dynamic data');

            $table->foreignId('fiscal_period_id')->nullable()->constrained('fiscal_periods')->nullOnDelete();
            $table->string('entry_number');
            $table->string('entry_type')->default('MANUAL')->comment('MANUAL, AUTO, SYSTEM, OPENING, CLOSING, ADJUSTMENT');
            $table->string('reference_type')->nullable()->comment('Polymorphic reference (Invoice, Payment, etc.)');
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->string('source_module')->nullable()->comment('Generic source module key');
            $table->string('source_type')->nullable()->comment('Generic source document/event type');
            $table->unsignedBigInteger('source_id')->nullable()->comment('Generic source identifier');
            $table->string('source_reference')->nullable()->comment('Human-readable source number/reference');
            $table->json('source_context')->nullable()->comment('Additional source context supplied by owning module');
            $table->text('description')->nullable();
            $table->date('entry_date');
            $table->date('posting_date')->nullable();
            $table->string('status')->default('DRAFT')->comment('DRAFT, POSTED, REVERSED');
            $table->unsignedBigInteger('currency_id')->nullable()->comment('Currency reference for journal header when applicable');
            $table->decimal('total_debit', 20, 4)->default(0)->comment('Backend-calculated journal debit total');
            $table->decimal('total_credit', 20, 4)->default(0)->comment('Backend-calculated journal credit total');
            $table->boolean('is_reversed')->default(false);
            $table->foreignId('reversal_entry_id')->nullable()->constrained('journal_entries')->nullOnDelete();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('posted_by')->nullable();
            $table->timestamp('posted_at')->nullable();
            $table->timestamp('reversed_at')->nullable();

            $table->timestamps();
            // No softDeletes - journal entries are immutable; reversals only

            $table->unique(['tenant_id', 'entry_number'], 'journal_entries_number_uk');
            $table->index(['tenant_id', 'entry_date'], 'journal_entries_date_idx');
            $table->index(['tenant_id', 'fiscal_period_id', 'status'], 'journal_entries_period_status_idx');
            $table->index(['tenant_id', 'reference_type', 'reference_id'], 'journal_entries_reference_idx');
            $table->index(['tenant_id', 'source_module', 'source_type', 'source_id'], 'journal_entries_source_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('journal_entries');
    }
};
