<?php

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
        Schema::create('kpi_metrics', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tenant_id')->nullable()->constrained('users')->cascadeOnDelete();
            $table->string('metric_name'); // revenue, customers_acquired, jobs_completed, etc.
            $table->string('metric_type'); // financial, operational, customer, etc.
            $table->decimal('metric_value', 15, 2);
            $table->string('unit')->nullable(); // currency, count, percentage, etc.
            $table->date('period_start');
            $table->date('period_end');
            $table->enum('period_type', ['daily', 'weekly', 'monthly', 'quarterly', 'yearly'])->default('daily');
            $table->json('breakdown')->nullable(); // Additional breakdown data
            $table->timestamps();

            $table->index(['tenant_id', 'metric_name', 'period_start']);
            $table->index(['metric_type', 'period_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kpi_metrics');
    }
};
