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
            $table->foreignId('tenant_id')->constrained(null, 'id', 'roles_tenant_id_fk')->cascadeOnDelete();
            $table->string('name');
            $table->string('guard_name')->default('api');
            $table->string('description')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'name', 'guard_name'], 'roles_tenant_name_guard_uk');
        });

        // Permissions
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained(null, 'id', 'permissions_tenant_id_fk')->cascadeOnDelete();
            $table->string('name');
            $table->string('guard_name')->default('api');
            $table->string('module')->nullable();
            $table->string('description')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'name', 'guard_name'], 'permissions_tenant_name_guard_uk');
        });

        // Pivot tables
        Schema::create('role_user', function (Blueprint $table) {
            $table->foreignId('tenant_id')->constrained(null, 'id', 'role_user_tenant_id_fk')->cascadeOnDelete();
            $table->foreignId('role_id')->constrained(null, 'id', 'role_user_role_id_fk')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained(null, 'id', 'role_user_user_id_fk')->cascadeOnDelete();
            $table->primary(['tenant_id', 'role_id', 'user_id'], 'role_user_tenant_role_user_pk');
        });

        Schema::create('permission_role', function (Blueprint $table) {
            $table->foreignId('tenant_id')->constrained(null, 'id', 'permission_role_tenant_id_fk')->cascadeOnDelete();
            $table->foreignId('permission_id')->constrained(null, 'id', 'permission_role_permission_id_fk')->cascadeOnDelete();
            $table->foreignId('role_id')->constrained(null, 'id', 'permission_role_role_id_fk')->cascadeOnDelete();
            $table->primary(['tenant_id', 'permission_id', 'role_id'], 'permission_role_tenant_permission_role_pk');
        });

        Schema::create('permission_user', function (Blueprint $table) {
            $table->foreignId('tenant_id')->constrained(null, 'id', 'permission_user_tenant_id_fk')->cascadeOnDelete();
            $table->foreignId('permission_id')->constrained(null, 'id', 'permission_user_permission_id_fk')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained(null, 'id', 'permission_user_user_id_fk')->cascadeOnDelete();
            $table->primary(['tenant_id', 'permission_id', 'user_id'], 'permission_user_tenant_permission_user_pk');
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
