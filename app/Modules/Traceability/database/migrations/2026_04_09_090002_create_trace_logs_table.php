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
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('entity_type', 100);
            $table->unsignedBigInteger('entity_id');
            $table->foreignId('identifier_id')->nullable()->constrained('identifiers')->nullOnDelete();
            $table->enum('action_type', ['received', 'issued', 'transferred', 'returned', 'adjusted', 'disposed', 'scanned', 'counted']);
            $table->string('reference_type', 100)->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->foreignId('source_location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->foreignId('destination_location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->decimal('quantity', 18, 4)->nullable();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->string('device_id', 100)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('timestamp');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trace_logs');
    }
};
