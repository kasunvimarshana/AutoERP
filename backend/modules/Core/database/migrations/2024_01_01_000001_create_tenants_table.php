<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table) {
<<<<<<<< HEAD:backend/database/migrations/2026_02_04_225041_create_tenants_table.php
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('subdomain')->unique();
            $table->string('database_name')->nullable(); // For schema-per-tenant or database-per-tenant
            $table->enum('isolation_strategy', ['row_level', 'schema', 'database'])->default('row_level');
            $table->enum('status', ['active', 'inactive', 'suspended'])->default('active');
            $table->json('settings')->nullable();
            $table->json('metadata')->nullable();
========
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name');
            $table->string('domain')->unique();
            $table->string('database')->unique();
            $table->enum('status', ['active', 'suspended', 'inactive'])->default('active');
            $table->json('settings')->nullable();
            $table->string('plan')->nullable();
>>>>>>>> kv-erp-001:backend/modules/Core/database/migrations/2024_01_01_000001_create_tenants_table.php
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('subscription_ends_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
<<<<<<<< HEAD:backend/database/migrations/2026_02_04_225041_create_tenants_table.php
            
            $table->index(['subdomain', 'status']);
========

            $table->index(['status', 'created_at']);
            $table->index('domain');
>>>>>>>> kv-erp-001:backend/modules/Core/database/migrations/2024_01_01_000001_create_tenants_table.php
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};
