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
        Schema::create('stock_locations', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('tenant_id');
            $table->ulid('warehouse_id');
            $table->ulid('parent_location_id')->nullable();
            $table->string('code', 50);
            $table->string('name', 255);
            $table->string('aisle', 50)->nullable();
            $table->string('bay', 50)->nullable();
            $table->string('shelf', 50)->nullable();
            $table->string('bin', 50)->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['tenant_id', 'warehouse_id']);
            $table->index(['tenant_id', 'parent_location_id']);
            $table->index(['tenant_id', 'code']);
            $table->index(['tenant_id', 'is_active']);

            // Composite indexes for common queries
            $table->index(['tenant_id', 'warehouse_id', 'is_active']);
            $table->index(['tenant_id', 'warehouse_id', 'aisle', 'bay', 'shelf', 'bin'], 'idx_location_hierarchy');

            // Unique constraint for code within tenant
            $table->unique(['tenant_id', 'code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_locations');
    }
};
