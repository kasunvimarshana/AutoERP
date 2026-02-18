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
        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('report_type'); // table, chart, kpi, export
            $table->string('module'); // inventory, sales, procurement, etc.
            $table->json('query_config');
            $table->json('schedule_config')->nullable();
            $table->boolean('is_public')->default(false);
            $table->foreignId('created_by_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['tenant_id', 'module']);
            $table->index(['tenant_id', 'report_type']);
            $table->index(['tenant_id', 'is_public']);
            $table->index('created_by_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};
