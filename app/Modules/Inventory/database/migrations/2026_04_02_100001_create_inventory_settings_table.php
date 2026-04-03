<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->string('valuation_method', 50)->default('fifo');
            $table->string('management_method', 50)->default('perpetual');
            $table->string('rotation_strategy', 50)->default('fefo');
            $table->string('allocation_algorithm', 50)->default('fefo');
            $table->string('cycle_count_method', 50)->default('abc');
            $table->boolean('negative_stock_allowed')->default(false);
            $table->boolean('track_lots')->default(true);
            $table->boolean('track_serial_numbers')->default(true);
            $table->boolean('track_expiry')->default(true);
            $table->boolean('auto_reorder')->default(false);
            $table->boolean('low_stock_alert')->default(true);
            $table->json('metadata')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index('tenant_id');
            $table->unique('tenant_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_settings');
    }
};
