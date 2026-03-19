<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_configs', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->index();
            $table->string('key');
            $table->text('value')->nullable();
            $table->string('type')->default('string'); // string, boolean, integer, float, json
            $table->string('group')->default('general'); // general, security, token, email, etc.
            $table->boolean('is_sensitive')->default(false);
            $table->text('description')->nullable();
            $table->timestamps();
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->unique(['tenant_id', 'key']);
        });

        Schema::create('feature_flags', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->nullable()->index(); // null = global flag
            $table->string('name')->index();
            $table->boolean('is_enabled')->default(false);
            $table->unsignedTinyInteger('rollout_percentage')->default(100);
            $table->json('conditions')->nullable(); // ABAC conditions
            $table->text('description')->nullable();
            $table->timestamps();
            $table->unique(['tenant_id', 'name']);
        });

        Schema::create('service_tokens', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('service_name');
            $table->string('client_id')->unique();
            $table->string('client_secret'); // hashed
            $table->json('allowed_scopes')->nullable();
            $table->json('allowed_ips')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
            $table->index(['client_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_tokens');
        Schema::dropIfExists('feature_flags');
        Schema::dropIfExists('tenant_configs');
    }
};
