<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void
    {
        Schema::create('saga_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->onDelete('cascade');
            $table->string('step_name');
            $table->string('status');
            $table->json('payload')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('executed_at')->nullable();
            $table->timestamp('compensated_at')->nullable();
            $table->timestamps();
            $table->index(['order_id', 'step_name']);
            $table->index(['order_id', 'status']);
        });
    }
    public function down(): void { Schema::dropIfExists('saga_logs'); }
};
