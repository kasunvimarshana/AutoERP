<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_roles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete()->comment('Multi-tenant owner reference');
            $table->unsignedBigInteger('organization_unit_id')->nullable()->comment('Optional organization-unit scope');
            $table->json('metadata')->nullable()->comment('Extensible custom dynamic data');

            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('role_id');
            $table->unsignedBigInteger('created_by')->nullable()->index('user_roles_created_by_idx');
            $table->unsignedBigInteger('updated_by')->nullable()->index('user_roles_updated_by_idx');

            $table->timestamps();

            $table->foreign(['organization_unit_id', 'tenant_id'], 'ur_org_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('organization_units')
                ->restrictOnDelete();
            $table->foreign(['user_id', 'tenant_id'], 'ur_user_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('users')
                ->cascadeOnDelete();
            $table->foreign(['role_id', 'tenant_id'], 'ur_role_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('roles')
                ->cascadeOnDelete();
            $table->unique(['tenant_id', 'user_id', 'role_id'], 'user_roles_uk');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_roles');
    }
};
