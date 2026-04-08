<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('order_lines', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->index();
            $table->string('order_type');
            $table->uuid('order_id');
            $table->string('product_id');
            $table->string('variant_id')->nullable();
            $table->text('description')->nullable();
            $table->decimal('quantity', 15, 4);
            $table->decimal('unit_price', 15, 4);
            $table->decimal('discount', 5, 2)->default(0);
            $table->decimal('tax_rate', 5, 2)->default(0);
            $table->decimal('line_total', 15, 2);
            $table->timestamps();
            $table->index(['tenant_id', 'order_type', 'order_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_lines');
    }
};
