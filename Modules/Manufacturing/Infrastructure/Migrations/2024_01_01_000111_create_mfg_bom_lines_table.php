<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('mfg_bom_lines', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->index();
            $table->uuid('bom_id')->index();
            $table->uuid('component_product_id')->index();
            $table->string('component_name');
            $table->decimal('quantity', 18, 8);
            $table->string('unit')->default('pcs');
            $table->decimal('scrap_rate', 5, 2)->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mfg_bom_lines');
    }
};
