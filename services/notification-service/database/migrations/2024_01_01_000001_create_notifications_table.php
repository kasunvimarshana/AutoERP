<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('tenant_id')->index();
            $table->string('recipient_id')->nullable()->index();
            $table->string('recipient_email')->nullable();
            $table->string('recipient_phone')->nullable();
            $table->string('saga_id')->nullable()->index();
            $table->string('order_id')->nullable()->index();
            $table->enum('type', [
                'order_confirmed',
                'order_cancelled',
                'order_failed',
                'payment_received',
                'stock_alert',
            ])->index();
            $table->enum('channel', ['email', 'sms', 'push'])->default('email');
            $table->enum('status', ['pending', 'sent', 'failed'])->default('pending')->index();
            $table->string('subject')->nullable();
            $table->longText('content')->nullable();
            $table->json('metadata')->nullable();
            $table->unsignedTinyInteger('retry_count')->default(0);
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
