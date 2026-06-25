<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('role_permissions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->restrictOnDelete()->comment('Multi-tenant owner reference');
            $table->json('metadata')->nullable()->comment('Extensible custom dynamic data');

            $table->unsignedBigInteger('role_id');
            $table->unsignedBigInteger('permission_id');
            $table->unsignedBigInteger('created_by')->nullable()->index('role_permissions_created_by_idx');
            $table->unsignedBigInteger('updated_by')->nullable()->index('role_permissions_updated_by_idx');

            $table->timestamps();
            $table->foreign(['role_id', 'tenant_id'], 'rp_role_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('roles')
                ->cascadeOnDelete();
            $table->foreign(['permission_id', 'tenant_id'], 'rp_permission_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('permissions')
                ->cascadeOnDelete();
            $table->unique(['tenant_id', 'role_id', 'permission_id'], 'role_permissions_uk');

            $table->unique(['id', 'tenant_id'], 'role_permissions_id_tenant_uk');

            $table->foreign(['created_by', 'tenant_id'], 'role_permissions_created_by_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('users')
                ->restrictOnDelete();
            $table->foreign(['updated_by', 'tenant_id'], 'role_permissions_updated_by_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('users')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('role_permissions');
    }
};
