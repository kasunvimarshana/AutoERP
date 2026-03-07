<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('webhooks', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('url', 500);
            $table->json('events');
            $table->string('secret', 128);
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedTinyInteger('max_retries')->default(3);
            $table->unsignedSmallInteger('timeout')->default(30);
            $table->json('custom_headers')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('last_triggered_at')->nullable();
            $table->unsignedSmallInteger('consecutive_failures')->default(0);
            $table->timestamps();

            $table->index(['tenant_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webhooks');
    }
};
