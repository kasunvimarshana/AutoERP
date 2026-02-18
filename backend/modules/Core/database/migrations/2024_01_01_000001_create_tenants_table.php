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
            $table->string('domain')->unique();
            $table->string('database')->unique();
            $table->enum('status', ['active', 'suspended', 'inactive'])->default('active');
            $table->json('settings')->nullable();
            $table->string('plan')->nullable();
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('subscription_ends_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'created_at']);
            $table->index('domain');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};
