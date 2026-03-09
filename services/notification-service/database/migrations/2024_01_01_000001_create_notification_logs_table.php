<?php
declare(strict_types=1);
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('notification_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('tenant_id', 36);
            $table->string('channel', 50)->default('email');
            $table->string('recipient', 255);
            $table->string('event', 100);
            $table->string('template', 100)->default('default');
            $table->json('payload')->nullable();
            $table->enum('status', ['pending','sent','failed'])->default('pending');
            $table->text('error_message')->nullable();
            $table->string('saga_id', 36)->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
            $table->index('tenant_id'); $table->index('status'); $table->index('saga_id'); $table->index('event');
        });
    }
    public function down(): void { Schema::dropIfExists('notification_logs'); }
};
