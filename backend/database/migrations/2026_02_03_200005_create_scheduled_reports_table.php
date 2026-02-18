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
        Schema::create('scheduled_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('report_id')->constrained()->onDelete('cascade');
            $table->string('schedule_cron'); // Cron expression
            $table->json('recipients'); // Array of email addresses
            $table->string('format'); // csv, pdf, excel, json
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_run_at')->nullable();
            $table->timestamp('next_run_at')->nullable();
            $table->timestamps();

            // Indexes
            $table->index(['tenant_id', 'report_id']);
            $table->index(['is_active', 'next_run_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scheduled_reports');
    }
};
