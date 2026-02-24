<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('qc_inspections', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->index();
            $table->uuid('quality_point_id')->nullable()->index();
            $table->string('reference_no')->nullable();
            $table->string('product_id')->nullable()->index();
            $table->string('lot_number')->nullable();
            $table->decimal('qty_inspected', 18, 8)->default(0);
            $table->decimal('qty_failed', 18, 8)->default(0);
            $table->string('status')->default('draft');
            $table->string('inspector_id')->nullable()->index();
            $table->text('notes')->nullable();
            $table->timestamp('inspected_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['tenant_id', 'reference_no']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('qc_inspections');
    }
};
