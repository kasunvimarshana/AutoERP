<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trace_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->string('entity_type', 100);
            $table->unsignedBigInteger('entity_id');
            $table->unsignedBigInteger('identifier_id')->nullable();
            $table->enum('action_type', ['received', 'issued', 'transferred', 'returned', 'adjusted', 'disposed', 'scanned', 'counted']);
            $table->string('reference_type', 100)->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->unsignedBigInteger('source_location_id')->nullable();
            $table->unsignedBigInteger('destination_location_id')->nullable();
            $table->decimal('quantity', 18, 4)->nullable();
            $table->unsignedBigInteger('user_id');
            $table->string('device_id', 100)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('timestamp')->useCurrent();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('identifier_id')->references('id')->on('identifiers')->nullOnDelete();
            $table->foreign('source_location_id')->references('id')->on('locations')->nullOnDelete();
            $table->foreign('destination_location_id')->references('id')->on('locations')->nullOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();

            $table->index(['tenant_id', 'entity_type', 'entity_id', 'timestamp']);
            $table->index(['reference_type', 'reference_id']);
            $table->index('action_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trace_logs');
    }
};