<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('webhook_deliveries', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('webhook_endpoint_id');
            $table->string('event_name');
            $table->json('payload');
            $table->string('status')->default('pending')->comment('pending, delivered, failed');
            $table->integer('attempt_count')->default(0);
            $table->integer('response_status')->nullable();
            $table->text('response_body')->nullable();
            $table->timestamp('last_attempt_at')->nullable();
            $table->timestamp('next_retry_at')->nullable();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('webhook_endpoint_id')->references('id')->on('webhook_endpoints')->onDelete('cascade');
            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'event_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webhook_deliveries');
    }
};
