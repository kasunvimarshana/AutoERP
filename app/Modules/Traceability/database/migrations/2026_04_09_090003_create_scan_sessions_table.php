<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scan_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->enum('session_type', ['receiving', 'picking', 'packing', 'shipping', 'counting', 'transfer']);
            $table->string('reference_type', 100);
            $table->unsignedBigInteger('reference_id');
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->json('device_info')->nullable();
            $table->timestamp('started_at');
            $table->timestamp('completed_at')->nullable();
            $table->enum('status', ['active', 'completed', 'aborted'])->default('active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scan_sessions');
    }
};
