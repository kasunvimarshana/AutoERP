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
        Schema::create('report_executions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('report_id')->constrained()->onDelete('cascade');
            $table->foreignId('executed_by_id')->nullable()->constrained('users')->onDelete('set null');
            $table->decimal('execution_time', 10, 3)->nullable(); // in seconds
            $table->json('parameters')->nullable();
            $table->integer('result_count')->default(0);
            $table->string('status'); // running, completed, failed
            $table->text('error_message')->nullable();
            $table->timestamps();

            // Indexes
            $table->index(['tenant_id', 'report_id']);
            $table->index(['tenant_id', 'status']);
            $table->index('executed_by_id');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('report_executions');
    }
};
