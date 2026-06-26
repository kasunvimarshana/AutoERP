<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const ACTIVE_STATUS = 'active';

    public function up(): void
    {
        Schema::create('user_organization_units', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1);
            $table->foreignId('tenant_id')->constrained('tenants', 'id', indexName: 'user_organization_units_tenant_fk')->restrictOnDelete();
            $table->unsignedBigInteger('organization_unit_id');
            $table->unsignedBigInteger('user_id');
            $table->string('status', 30)->default(self::ACTIVE_STATUS);
            $table->boolean('is_default')->default(false);
            $table->string('default_marker', 16)->nullable();
            $table->unsignedBigInteger('created_by_user_id')->nullable();
            $table->unsignedBigInteger('updated_by_user_id')->nullable();
            $table->timestamps();

            $table->unique(['id', 'tenant_id'], 'user_organization_units_id_tenant_uk');
            $table->unique(['tenant_id', 'user_id', 'organization_unit_id'], 'user_org_units_assignment_uk');
            $table->unique(['tenant_id', 'user_id', 'default_marker'], 'user_org_units_one_default_uk');
            $table->index(['tenant_id', 'user_id', 'status'], 'user_org_units_access_ix');
            $table->foreign(['organization_unit_id', 'tenant_id'], 'user_org_units_org_tenant_fk')
                ->references(['id', 'tenant_id'])->on('organization_units')->restrictOnDelete();
            $table->foreign(['user_id', 'tenant_id'], 'user_org_units_user_tenant_fk')
                ->references(['id', 'tenant_id'])->on('users')->cascadeOnDelete();
            foreach (['created_by_user_id', 'updated_by_user_id'] as $column) {
                $table->foreign([$column, 'tenant_id'], 'user_org_units_'.$column.'_tenant_fk')
                    ->references(['id', 'tenant_id'])->on('users')->restrictOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_organization_units');
    }
};
