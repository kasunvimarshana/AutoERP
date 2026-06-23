<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $guardName = (string) config('auth.defaults.guard', 'api');

        Schema::create('roles', function (Blueprint $table) use ($guardName) {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete()->comment('Multi-tenant owner reference');
            $table->unsignedBigInteger('organization_unit_id')->nullable()->comment('Optional organization-unit scope');
            $table->json('metadata')->nullable()->comment('Extensible custom dynamic data');

            $table->string('name');
            $table->string('guard_name')->default($guardName);
            $table->string('description')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['id', 'tenant_id'], 'roles_id_tenant_uk');
            $table->foreign(['organization_unit_id', 'tenant_id'], 'roles_org_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('organization_units')
                ->restrictOnDelete();
            $table->unique(['tenant_id', 'name', 'guard_name'], 'roles_name_guard_uk');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};
