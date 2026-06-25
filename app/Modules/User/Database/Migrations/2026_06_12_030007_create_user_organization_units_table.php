<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\User\Constants\UserOrganizationUnitStatus;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_organization_units', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1);
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->restrictOnDelete();
            $table->foreignId('organization_unit_id');
            $table->foreignId('user_id');
            $table->enum('status', UserOrganizationUnitStatus::values())->default(UserOrganizationUnitStatus::ACTIVE);
            $table->boolean('is_default')->default(false);
            $table->string('default_marker', 16)->nullable();
            $table->timestamps();

            $table->foreign(['organization_unit_id', 'tenant_id'], 'user_org_units_org_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('organization_units')
                ->restrictOnDelete();
            $table->foreign(['user_id', 'tenant_id'], 'user_org_units_user_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('users')
                ->cascadeOnDelete();
            $table->unique(
                ['tenant_id', 'user_id', 'organization_unit_id'],
                'user_org_units_assignment_uk',
            );
            $table->unique(
                ['tenant_id', 'user_id', 'default_marker'],
                'user_org_units_one_default_uk',
            );
            $table->index(
                ['tenant_id', 'user_id', 'status'],
                'user_org_units_access_idx',
            );

            $table->unique(['id', 'tenant_id'], 'user_organization_units_id_tenant_uk');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_organization_units');
    }
};
