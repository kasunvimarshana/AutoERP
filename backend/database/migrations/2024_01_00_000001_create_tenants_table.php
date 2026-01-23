<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('domain')->nullable()->unique();
            $table->string('database')->nullable();
            $table->enum('status', ['active', 'suspended', 'inactive'])->default('active');
            $table->enum('subscription_status', ['trial', 'active', 'expired', 'cancelled'])->default('trial');
            $table->string('subscription_plan')->nullable();
            $table->timestamp('subscription_started_at')->nullable();
            $table->timestamp('subscription_expires_at')->nullable();
            $table->integer('max_users')->default(10);
            $table->integer('max_branches')->default(1);
            $table->json('settings')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'subscription_status']);
            $table->index('subscription_expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};
