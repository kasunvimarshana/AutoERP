<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('webhook_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id', 100)->index();
            $table->string('name', 255);
            $table->string('url', 2048);
            $table->json('events')->comment('Array of event names, use ["*"] for all events');
            $table->string('secret', 255)->nullable()->comment('Per-subscription HMAC signing secret');
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedSmallInteger('failure_count')->default(0);
            $table->timestamp('last_triggered_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webhook_subscriptions');
    }
};
