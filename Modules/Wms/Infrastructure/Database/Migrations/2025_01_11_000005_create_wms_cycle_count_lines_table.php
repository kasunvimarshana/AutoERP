<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wms_cycle_count_lines', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('cycle_count_id');
            $table->foreign('cycle_count_id')->references('id')->on('wms_cycle_counts')->cascadeOnDelete();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('product_id')->index();
            $table->unsignedBigInteger('bin_id')->nullable();
            $table->decimal('system_qty', 15, 4)->default(0);
            $table->decimal('counted_qty', 15, 4)->default(0);
            $table->decimal('variance', 15, 4)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['cycle_count_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wms_cycle_count_lines');
    }
};
