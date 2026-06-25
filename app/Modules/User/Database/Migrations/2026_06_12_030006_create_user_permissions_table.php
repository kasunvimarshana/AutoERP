<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_permissions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete()->comment('Multi-tenant owner reference');
            $table->unsignedBigInteger('organization_unit_id')->nullable()->comment('Optional organization-unit scope');
            $table->json('metadata')->nullable()->comment('Extensible custom dynamic data');

            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('permission_id');
            $table->unsignedBigInteger('created_by')->nullable()->index('user_permissions_created_by_idx');
            $table->unsignedBigInteger('updated_by')->nullable()->index('user_permissions_updated_by_idx');

            $table->timestamps();

            $table->foreign(['organization_unit_id', 'tenant_id'], 'up_org_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('organization_units')
                ->restrictOnDelete();
            $table->foreign(['user_id', 'tenant_id'], 'up_user_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('users')
                ->cascadeOnDelete();
            $table->foreign(['permission_id', 'tenant_id'], 'up_permission_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('permissions')
                ->cascadeOnDelete();
            $table->unique(['tenant_id', 'user_id', 'permission_id'], 'user_permissions_uk');

            $table->unique(['id', 'tenant_id'], 'user_permissions_id_tenant_uk');

            $table->foreign(['created_by', 'tenant_id'], 'user_permissions_created_by_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('users')
                ->restrictOnDelete();
            $table->foreign(['updated_by', 'tenant_id'], 'user_permissions_updated_by_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('users')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_permissions');
    }
};
