<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('production_orders', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->string('reference_no', 50)->unique();
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('variant_id')->nullable();
            $table->unsignedBigInteger('warehouse_id');
            $table->unsignedBigInteger('bom_id');
            $table->decimal('planned_quantity', 15, 4);
            $table->decimal('produced_quantity', 15, 4)->default('0.0000');
            $table->decimal('total_cost', 15, 4)->default('0.0000');
            $table->decimal('wastage_percent', 5, 2)->default('0.00');
            $table->string('status', 20)->default('draft');
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'status']);
            $table->foreign('bom_id')->references('id')->on('boms');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('production_orders');
    }
};
