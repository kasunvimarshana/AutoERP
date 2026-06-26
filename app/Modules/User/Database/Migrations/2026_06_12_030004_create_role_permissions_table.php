<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('role_permissions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1);
            $table->foreignId('tenant_id')->constrained('tenants', 'id', indexName: 'role_permissions_tenant_fk')->restrictOnDelete();
            $table->unsignedBigInteger('role_id');
            $table->unsignedBigInteger('permission_id');
            $table->unsignedBigInteger('created_by_user_id')->nullable();
            $table->unsignedBigInteger('updated_by_user_id')->nullable();
            $table->timestamps();

            $table->unique(['id', 'tenant_id'], 'role_permissions_id_tenant_uk');
            $table->unique(['tenant_id', 'role_id', 'permission_id'], 'role_permissions_uk');
            $table->foreign(['role_id', 'tenant_id'], 'role_permissions_role_tenant_fk')
                ->references(['id', 'tenant_id'])->on('roles')->cascadeOnDelete();
            $table->foreign(['permission_id', 'tenant_id'], 'role_permissions_permission_tenant_fk')
                ->references(['id', 'tenant_id'])->on('permissions')->restrictOnDelete();
            foreach (['created_by_user_id', 'updated_by_user_id'] as $column) {
                $table->foreign([$column, 'tenant_id'], 'role_permissions_'.$column.'_tenant_fk')
                    ->references(['id', 'tenant_id'])->on('users')->restrictOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('role_permissions');
    }
};
