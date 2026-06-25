<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\OrganizationUnit\Constants\OrganizationUnitHierarchy;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organization_units', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Optimistic concurrency version.');
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->restrictOnDelete();
            $table->unsignedBigInteger('type_id');
            $table->unsignedBigInteger('parent_id')->nullable();

            $table->string('name');
            $table->string('code', 100);
            $table->string('path', 1024)->comment('Server-derived readable materialized path.');
            $table->char('path_hash', 64)->comment('SHA-256 hierarchy uniqueness key.');
            $table->unsignedInteger('depth')->comment('Server-derived hierarchy depth.');
            $table->enum('root_marker', OrganizationUnitHierarchy::rootMarkerValues())
                ->nullable()
                ->comment('Set only for the protected tenant root.');
            $table->boolean('is_active')->default(true);
            $table->timestamp('retired_at')->nullable();
            $table->text('description')->nullable();

            $table->string('logo_object_key')->nullable();
            $table->string('logo_mime_type', 100)->nullable();
            $table->unsignedBigInteger('logo_size_bytes')->nullable();

            $table->timestamps();

            $table->unique(['id', 'tenant_id'], 'organization_units_id_tenant_uk');
            $table->foreign(['type_id', 'tenant_id'], 'organization_units_type_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('organization_unit_types')
                ->restrictOnDelete();
            $table->foreign(['parent_id', 'tenant_id'], 'organization_units_parent_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('organization_units')
                ->restrictOnDelete();
            $table->unique(['tenant_id', 'path_hash'], 'organization_units_path_hash_uk');
            $table->unique(['tenant_id', 'code'], 'organization_units_code_uk');
            $table->unique(['tenant_id', 'root_marker'], 'organization_units_root_uk');
            $table->index(['tenant_id', 'parent_id', 'is_active', 'retired_at'], 'organization_units_parent_state_idx');
            $table->index(['tenant_id', 'name'], 'organization_units_name_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organization_units');
    }
};
