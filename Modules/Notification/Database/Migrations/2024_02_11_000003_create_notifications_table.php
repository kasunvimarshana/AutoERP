<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('tenant_id');
            $table->ulid('organization_id');
            $table->ulid('user_id');
            $table->ulid('template_id')->nullable();
            $table->string('type', 50);
            $table->string('channel', 50);
            $table->string('priority', 20)->default('normal');
            $table->string('status', 20)->default('pending');
            $table->string('subject', 500)->nullable();
            $table->text('body')->nullable();
            $table->json('data')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->text('error_message')->nullable();
            $table->integer('retry_count')->default(0);
            $table->integer('max_retries')->default(3);
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Foreign keys
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('template_id')->references('id')->on('notification_templates')->onDelete('set null');

            // Indexes
            $table->index(['tenant_id', 'organization_id']);
            $table->index(['tenant_id', 'user_id']);
            $table->index(['tenant_id', 'template_id']);
            $table->index(['tenant_id', 'type']);
            $table->index(['tenant_id', 'channel']);
            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'priority']);
            $table->index(['tenant_id', 'scheduled_at']);
            $table->index(['tenant_id', 'read_at']);
            $table->index(['tenant_id', 'sent_at']);

            // Composite indexes for common queries
            $table->index(['tenant_id', 'user_id', 'status']);
            $table->index(['tenant_id', 'user_id', 'read_at']);
            $table->index(['tenant_id', 'status', 'scheduled_at']);
            $table->index(['tenant_id', 'status', 'retry_count']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
