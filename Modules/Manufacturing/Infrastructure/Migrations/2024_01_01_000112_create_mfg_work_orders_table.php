<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('mfg_work_orders', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->index();
            $table->uuid('bom_id')->index();
            $table->string('reference_no');
            $table->decimal('quantity_planned', 18, 8);
            $table->decimal('quantity_produced', 18, 8)->default(0);
            $table->string('status')->default('draft');
            $table->date('scheduled_start')->nullable();
            $table->date('scheduled_end')->nullable();
            $table->timestamp('actual_start')->nullable();
            $table->timestamp('actual_end')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['tenant_id', 'reference_no']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mfg_work_orders');
    }
};
