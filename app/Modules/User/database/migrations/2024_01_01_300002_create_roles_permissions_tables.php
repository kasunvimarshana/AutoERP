<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Roles
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('guard_name')->default('api');
            $table->string('description')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'name', 'guard_name'], 'uq_roles_tenant_name_guard');
        });

        // Permissions
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('guard_name')->default('api');
            $table->string('module')->nullable();
            $table->string('description')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'name', 'guard_name'], 'uq_permissions_tenant_name_guard');
        });

        // Pivot tables
        Schema::create('role_user', function (Blueprint $table) {
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->primary(['tenant_id', 'role_id', 'user_id'], 'pk_role_user_tenant_role_user');
        });

        Schema::create('permission_role', function (Blueprint $table) {
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('permission_id')->constrained()->cascadeOnDelete();
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();

            $table->primary(['tenant_id', 'permission_id', 'role_id'], 'pk_permission_role_tenant_perm_role');
        });

        Schema::create('permission_user', function (Blueprint $table) {
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('permission_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->primary(['tenant_id', 'permission_id', 'user_id'], 'pk_permission_user_tenant_perm_user');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('permission_user');
        Schema::dropIfExists('permission_role');
        Schema::dropIfExists('role_user');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('roles');
    }
};