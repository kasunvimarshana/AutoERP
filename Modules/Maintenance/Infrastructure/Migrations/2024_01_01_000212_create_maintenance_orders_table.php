<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('maintenance_orders', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->index();
            $table->string('reference')->unique();
            $table->uuid('equipment_id')->index();
            $table->string('order_type');
            $table->text('description')->nullable();
            $table->date('scheduled_date')->nullable();
            $table->string('assigned_to')->nullable();
            $table->decimal('labor_cost', 18, 8)->default('0.00000000');
            $table->decimal('parts_cost', 18, 8)->default('0.00000000');
            $table->string('status')->default('draft');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenance_orders');
    }
};
