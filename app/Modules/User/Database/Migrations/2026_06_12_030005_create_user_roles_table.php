<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_roles', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1);
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->restrictOnDelete();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('role_id');
            $table->unsignedBigInteger('created_by_user_id')->nullable();
            $table->unsignedBigInteger('updated_by_user_id')->nullable();
            $table->timestamps();

            $table->unique(['id', 'tenant_id'], 'user_roles_id_tenant_uk');
            $table->unique(['tenant_id', 'user_id', 'role_id'], 'user_roles_uk');
            $table->foreign(['user_id', 'tenant_id'], 'user_roles_user_tenant_fk')
                ->references(['id', 'tenant_id'])->on('users')->cascadeOnDelete();
            $table->foreign(['role_id', 'tenant_id'], 'user_roles_role_tenant_fk')
                ->references(['id', 'tenant_id'])->on('roles')->restrictOnDelete();
            foreach (['created_by_user_id', 'updated_by_user_id'] as $column) {
                $table->foreign([$column, 'tenant_id'], 'user_roles_'.$column.'_tenant_fk')
                    ->references(['id', 'tenant_id'])->on('users')->restrictOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_roles');
    }
};
