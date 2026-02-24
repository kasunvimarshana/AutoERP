<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('mfg_boms', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->index();
            $table->uuid('product_id')->index();
            $table->string('product_name');
            $table->string('version')->default('1.0');
            $table->decimal('quantity', 18, 8)->default(1);
            $table->string('unit')->default('pcs');
            $table->string('status')->default('draft');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mfg_boms');
    }
};
