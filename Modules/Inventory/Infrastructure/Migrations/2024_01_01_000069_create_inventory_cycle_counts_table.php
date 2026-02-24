<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_cycle_counts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->index();
            $table->uuid('warehouse_id')->index();
            $table->uuid('location_id')->nullable()->index();

            // Human-readable reference number, e.g. CC-2024-A1B2C3
            $table->string('reference', 30)->unique();

            $table->date('count_date');

            // draft | in_progress | posted | cancelled
            $table->string('status', 15)->default('draft');

            $table->text('notes')->nullable();

            $table->softDeletes();
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_cycle_counts');
    }
};
