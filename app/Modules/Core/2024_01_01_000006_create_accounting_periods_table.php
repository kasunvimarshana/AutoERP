<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Accounting Periods — critical for accrual accounting, period-based
     * reporting, and GAAP/IFRS compliance. Every transaction must be assigned
     * to an open accounting period via period_id, not just a raw date.
     */
    public function up(): void
    {
        Schema::create('accounting_periods', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('fiscal_year_id');
            $table->string('name', 100);           // e.g. "Jan 2024"
            $table->unsignedTinyInteger('period_number'); // 1–12 (or 1–13 for 4-4-5)
            $table->date('start_date');
            $table->date('end_date');
            $table->enum('status', ['open', 'closed', 'locked'])->default('open');
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('fiscal_year_id')->references('id')->on('fiscal_years')->cascadeOnDelete();

            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'start_date', 'end_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounting_periods');
    }
};
