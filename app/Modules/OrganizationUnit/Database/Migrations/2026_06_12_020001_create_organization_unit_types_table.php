<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organization_unit_types', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Optimistic concurrency version.');
            $table->foreignId('tenant_id')->constrained('tenants', 'id', indexName: 'organization_unit_types_tenant_fk')->restrictOnDelete();
            $table->string('name');
            $table->char('name_key', 64)->comment('Case-insensitive type-name uniqueness key.');
            $table->unsignedInteger('level')->comment('Required hierarchy depth for this organization-unit type.');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['id', 'tenant_id'], 'organization_unit_types_id_tenant_uk');
            $table->unique(['tenant_id', 'name_key'], 'organization_unit_types_name_key_uk');
            $table->index(['tenant_id', 'level', 'is_active'], 'organization_unit_types_level_active_ix');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organization_unit_types');
    }
};
