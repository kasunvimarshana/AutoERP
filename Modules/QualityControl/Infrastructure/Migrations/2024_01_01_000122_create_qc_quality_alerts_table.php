<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('qc_quality_alerts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->index();
            $table->uuid('inspection_id')->nullable()->index();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('product_id')->nullable()->index();
            $table->string('lot_number')->nullable();
            $table->string('priority')->default('medium');
            $table->string('status')->default('open');
            $table->string('assigned_to')->nullable()->index();
            $table->timestamp('deadline')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('qc_quality_alerts');
    }
};
