<?php

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
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained('tenants')->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('log_name', 50)->nullable()->comment('Module or category name');
            $table->text('description');
            $table->string('subject_type');
            $table->unsignedBigInteger('subject_id');
            $table->string('causer_type');
            $table->unsignedBigInteger('causer_id');
            $table->json('properties')->nullable()->comment('Additional data');
            $table->timestamp('created_at');

            $table->index(['tenant_id', 'created_at']);
            $table->index('log_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
