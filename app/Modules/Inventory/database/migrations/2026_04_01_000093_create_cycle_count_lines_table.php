<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('cycle_count_lines', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->index();
            $table->uuid('cycle_count_id')->index();
            $table->string('product_id');
            $table->string('variant_id')->nullable();
            $table->decimal('system_qty', 15, 4)->default(0);
            $table->decimal('counted_qty', 15, 4)->nullable();
            $table->decimal('variance', 15, 4)->nullable();
            $table->string('batch_number')->nullable();
            $table->string('lot_number')->nullable();
            $table->string('serial_number')->nullable();
            $table->timestamps();
            $table->index(['tenant_id', 'cycle_count_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cycle_count_lines');
    }
};
