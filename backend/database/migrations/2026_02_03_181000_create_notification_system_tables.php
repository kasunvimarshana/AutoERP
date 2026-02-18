<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Push notification subscriptions table
        Schema::create('push_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('endpoint')->unique();
            $table->text('public_key')->nullable();
            $table->text('auth_token')->nullable();
            $table->string('content_encoding')->default('aesgcm');
            $table->timestamps();

            $table->index(['tenant_id', 'user_id']);
        });

        // Notification preferences table
        Schema::create('notification_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('channel'); // 'web_push', 'email', 'sms'
            $table->string('event_type'); // 'sales_order_created', 'invoice_created', etc.
            $table->boolean('enabled')->default(true);
            $table->timestamps();

            $table->unique(['user_id', 'channel', 'event_type']);
            $table->index(['tenant_id', 'user_id']);
        });

        // Notification queue table (for retry logic)
        Schema::create('notification_queue', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('channel'); // 'web_push', 'email', 'sms'
            $table->string('type'); // Notification type
            $table->json('data'); // Notification payload
            $table->string('status')->default('pending'); // pending, sent, failed
            $table->integer('attempts')->default(0);
            $table->text('error_message')->nullable();
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'user_id']);
            $table->index(['status', 'scheduled_at']);
        });

        // Add columns to existing notifications table if needed
        if (Schema::hasTable('notifications') && ! Schema::hasColumn('notifications', 'channel')) {
            Schema::table('notifications', function (Blueprint $table) {
                $table->string('channel')->default('database')->after('type');
                $table->string('priority')->default('normal')->after('channel'); // low, normal, high, urgent
                $table->json('action_url')->nullable()->after('data');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_queue');
        Schema::dropIfExists('notification_preferences');
        Schema::dropIfExists('push_subscriptions');

        if (Schema::hasTable('notifications')) {
            Schema::table('notifications', function (Blueprint $table) {
                if (Schema::hasColumn('notifications', 'channel')) {
                    $table->dropColumn(['channel', 'priority', 'action_url']);
                }
            });
        }
    }
};
