<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounting_periods', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->index();
            $table->string('name');
            $table->date('start_date');
            $table->date('end_date');
            $table->string('status', 20)->default('draft'); // draft|open|closed|locked
            $table->uuid('closed_by')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->uuid('locked_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'start_date', 'end_date'], 'accounting_periods_tenant_dates_idx');
            $table->index(['tenant_id', 'status'], 'accounting_periods_tenant_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounting_periods');
    }
};
