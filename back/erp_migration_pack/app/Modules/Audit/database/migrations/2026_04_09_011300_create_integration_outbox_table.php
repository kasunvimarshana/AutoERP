<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('integration_outbox', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained('tenants')->nullOnDelete();
            $table->string('event_name');
            $table->string('aggregate_type');
            $table->unsignedBigInteger('aggregate_id');
            $table->json('payload')->nullable();
            $table->string('status')->default("pending");
            $table->timestamp('available_at')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->text('last_error')->nullable();
            $table->index(['status', 'available_at']);
            $table->index(['aggregate_type', 'aggregate_id']);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('integration_outbox');
    }
};
