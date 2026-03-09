<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('subdomain')->unique();
            $table->string('plan', 50)->default('free');
            $table->string('status', 50)->default('active')->index();
            $table->jsonb('settings')->nullable();
            $table->jsonb('features')->nullable();
            $table->jsonb('config')->nullable();
            $table->jsonb('metadata')->nullable();
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('subscription_ends_at')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index('plan');
            $table->index('subdomain');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};
