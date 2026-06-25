<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_categories', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable();
            $table->foreignId('parent_id')->nullable();
            $table->string('code');
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'code'], 'customer_categories_tenant_code_uk');
            $table->index(['tenant_id', 'organization_unit_id'], 'customer_categories_tenant_org_idx');
            $table->index('parent_id', 'customer_categories_parent_idx');

            $table->unique(['id', 'tenant_id'], 'customer_categories_id_tenant_uk');
            $table->foreign(['organization_unit_id', 'tenant_id'], 'customer_categories_organization_unit_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('organization_units')
                ->restrictOnDelete();
            $table->foreign(['parent_id', 'tenant_id'], 'customer_categories_parent_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('customer_categories')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_categories');
    }
};
