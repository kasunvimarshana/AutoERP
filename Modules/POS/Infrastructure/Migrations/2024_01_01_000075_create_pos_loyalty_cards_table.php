<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pos_loyalty_cards', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->index();
            $table->uuid('program_id')->index();
            $table->uuid('customer_id')->index();
            // Integer point balance stored as BIGINT for precision
            $table->bigInteger('points_balance')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // One card per customer per program
            $table->unique(['tenant_id', 'program_id', 'customer_id']);

            $table->foreign('program_id')
                ->references('id')
                ->on('pos_loyalty_programs')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pos_loyalty_cards');
    }
};
