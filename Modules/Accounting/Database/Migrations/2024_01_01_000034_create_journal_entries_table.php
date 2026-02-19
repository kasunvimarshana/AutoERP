<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('journal_entries', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('tenant_id');
            $table->ulid('organization_id');
            $table->ulid('fiscal_period_id')->nullable();
            $table->string('entry_number', 50)->unique();
            $table->date('entry_date');
            $table->string('reference', 100)->nullable();
            $table->text('description')->nullable();
            $table->string('status', 20)->default('draft');
            $table->string('source_type', 100)->nullable();
            $table->ulid('source_id')->nullable();
            $table->timestamp('posted_at')->nullable();
            $table->ulid('posted_by')->nullable();
            $table->timestamp('reversed_at')->nullable();
            $table->ulid('reversed_by')->nullable();
            $table->ulid('reversal_entry_id')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Foreign keys
            $table->foreign('fiscal_period_id')->references('id')->on('fiscal_periods')->onDelete('set null');
            $table->foreign('reversal_entry_id')->references('id')->on('journal_entries')->onDelete('set null');

            // Indexes
            $table->index(['tenant_id', 'organization_id']);
            $table->index(['tenant_id', 'fiscal_period_id']);
            $table->index(['tenant_id', 'entry_date']);
            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'entry_number']);
            $table->index(['tenant_id', 'source_type', 'source_id']);
            $table->index(['tenant_id', 'posted_at']);
            $table->index(['tenant_id', 'reversed_at']);

            // Composite indexes
            $table->index(['tenant_id', 'organization_id', 'status']);
            $table->index(['tenant_id', 'organization_id', 'entry_date']);
            $table->index(['tenant_id', 'fiscal_period_id', 'status']);
            $table->index(['tenant_id', 'fiscal_period_id', 'entry_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('journal_entries');
    }
};
