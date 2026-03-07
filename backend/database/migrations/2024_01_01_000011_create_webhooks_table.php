<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('webhooks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('url');
            $table->string('secret')->nullable();
            $table->json('events');
            $table->enum('status', ['active', 'inactive', 'disabled'])->default('active');
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('last_response_code')->nullable();
            $table->timestamp('last_triggered_at')->nullable();
            $table->unsignedSmallInteger('failure_count')->default(0);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index('tenant_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webhooks');
    }
};
