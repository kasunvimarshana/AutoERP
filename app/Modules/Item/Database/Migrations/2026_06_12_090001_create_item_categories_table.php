<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('item_categories', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants', indexName: 'item_categories_tenant_fk')->restrictOnDelete();
            $table->foreignId('organization_unit_id')->nullable();
            $table->foreignId('parent_id')->nullable();
            $table->string('code', 50);
            $table->string('name');
            $table->text('description')->nullable();
            $table->json('metadata')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'code'], 'item_categories_tenant_code_uk');
            $table->index('tenant_id', 'item_categories_tenant_ix');
            $table->index('organization_unit_id', 'item_categories_org_ix');
            $table->index('parent_id', 'item_categories_parent_ix');

            $table->unique(['id', 'tenant_id'], 'item_categories_id_tenant_uk');
            $table->foreign(['organization_unit_id', 'tenant_id'], 'item_categories_organization_unit_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('organization_units')
                ->restrictOnDelete();
            $table->foreign(['parent_id', 'tenant_id'], 'item_categories_parent_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('item_categories')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('item_categories');
    }
};
