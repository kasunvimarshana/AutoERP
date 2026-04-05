<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications_log', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable()->index();
            $table->unsignedBigInteger('user_id')->index();
            $table->string('type', 100)->index();
            $table->string('channel', 30);
            $table->string('title');
            $table->text('body');
            $table->json('data')->nullable();
            $table->string('status', 20)->default('pending');
            $table->timestamp('read_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'user_id', 'read_at']);
            $table->index(['tenant_id', 'user_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications_log');
    }
};
