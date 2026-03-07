<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')
                ->constrained('tenants')
                ->cascadeOnDelete();
            $table->string('keycloak_id')->nullable()->comment('Keycloak subject (sub) claim');
            $table->string('name');
            $table->string('email');
            $table->string('username')->nullable();
            $table->string('role')->default('viewer')->comment('super-admin|admin|manager|staff|viewer');
            $table->string('status')->default('active')->comment('active|inactive|suspended');
            $table->jsonb('profile')->nullable();
            $table->jsonb('permissions')->nullable();
            $table->jsonb('metadata')->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Unique constraints scoped to tenant
            $table->unique(['tenant_id', 'email']);
            $table->unique(['tenant_id', 'username']);
            $table->unique(['tenant_id', 'keycloak_id']);

            $table->index('tenant_id');
            $table->index('role');
            $table->index('status');
            $table->index('email');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
