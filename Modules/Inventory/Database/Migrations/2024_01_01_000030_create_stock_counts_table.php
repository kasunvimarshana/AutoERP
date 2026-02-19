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
        Schema::create('stock_counts', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('tenant_id');
            $table->ulid('warehouse_id');
            $table->string('count_number', 50);
            $table->string('status', 20)->default('planned');
            $table->date('count_date');
            $table->date('scheduled_date')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('reconciled_at')->nullable();
            $table->string('counted_by', 255)->nullable();
            $table->string('approved_by', 255)->nullable();
            $table->text('notes')->nullable();
            $table->ulid('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['tenant_id', 'warehouse_id']);
            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'count_date']);
            $table->index(['tenant_id', 'count_number']);

            // Composite indexes for common queries
            $table->index(['tenant_id', 'warehouse_id', 'status']);
            $table->index(['tenant_id', 'warehouse_id', 'count_date']);
            $table->index(['tenant_id', 'status', 'count_date']);

            // Unique constraint scoped to tenant
            $table->unique(['tenant_id', 'count_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_counts');
    }
};
