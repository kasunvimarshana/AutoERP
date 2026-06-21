<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organization_units', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete()->comment('Multi-tenant owner reference');
            $table->json('metadata')->nullable()->comment('Extensible custom dynamic data');

            $table->foreignId('type_id')->nullable()->constrained('organization_unit_types', 'id', 'organization_units_type_id_fk')->nullOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('organization_units', 'id', 'organization_units_parent_id_fk')->nullOnDelete();
            $table->string('name');
            $table->string('code')->nullable();
            $table->string('image_path')->nullable();
            $table->string('path')->nullable()->comment('materialized path for quick tree queries');
            $table->unsignedInteger('depth')->default(0);
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            $table->integer('_lft')->default(0);
            $table->integer('_rgt')->default(0);

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['id', 'tenant_id'], 'organization_units_id_tenant_uk');
            $table->unique(['tenant_id', 'name'], 'organization_units_name_uk');
            $table->index(['tenant_id', 'parent_id'], 'organization_units_parent_id_idx');
            $table->index(['tenant_id', 'path'], 'organization_units_path_idx');
            $table->index(['tenant_id', '_lft', '_rgt'], 'organization_units_lft_rgt_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organization_units');
    }
};
