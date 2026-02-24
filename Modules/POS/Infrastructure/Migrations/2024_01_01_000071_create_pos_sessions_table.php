<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('pos_sessions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->index();
            $table->uuid('terminal_id')->index();
            $table->uuid('cashier_id')->nullable()->index();
            $table->string('status')->default('open');
            $table->decimal('opening_cash', 18, 8)->default(0);
            $table->decimal('closing_cash', 18, 8)->nullable();
            $table->decimal('total_sales', 18, 8)->default(0);
            $table->unsignedInteger('order_count')->default(0);
            $table->timestamp('opened_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pos_sessions');
    }
};
