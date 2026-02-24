<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('purchase_requisition_lines', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('requisition_id')->index();
            $table->uuid('product_id')->nullable()->index();
            $table->decimal('qty', 18, 8)->default(1);
            $table->decimal('unit_price', 18, 8)->default(0);
            $table->decimal('line_total', 18, 8)->default(0);
            $table->string('uom')->nullable();
            $table->date('required_by_date')->nullable();
            $table->text('justification')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_requisition_lines');
    }
};
