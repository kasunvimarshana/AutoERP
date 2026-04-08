<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cycle_counts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->string('count_number', 50);
            $table->uuid('warehouse_id');
            $table->uuid('location_id')->nullable();
            // draft, submitted, approved, cancelled
            $table->string('status', 30)->default('draft');
            $table->dateTime('counted_at');
            $table->dateTime('completed_at')->nullable();
            $table->unsignedBigInteger('counted_by');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'count_number']);
            $table->index(['tenant_id', 'warehouse_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cycle_counts');
    }
};
