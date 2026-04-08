<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_components', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('parent_variant_id')->constrained('product_variants')->cascadeOnDelete();
            $table->foreignId('component_variant_id')->constrained('product_variants')->cascadeOnDelete();
            $table->foreignId('unit_of_measure_id')->nullable()->constrained('units_of_measure')->nullOnDelete();
            $table->decimal('quantity', 24, 8);
            $table->string('component_role')->default("standard");
            $table->unique(['parent_variant_id', 'component_variant_id']);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_components');
    }
};
