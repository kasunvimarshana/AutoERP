<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('saga_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('saga_id')->unique();
            $table->string('saga_type');
            $table->string('status');  // started|completed|failed|compensating|compensated
            $table->string('current_step')->nullable();
            $table->json('completed_steps')->nullable();
            $table->string('failed_step')->nullable();
            $table->json('context')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index('saga_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('saga_transactions');
    }
};
