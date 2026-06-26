<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('item_usage_rules', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants', indexName: 'item_usage_rules_tenant_fk')->restrictOnDelete();
            $table->foreignId('organization_unit_id')->nullable();
            $table->foreignId('item_id');
            $table->string('module_code', 50);
            $table->boolean('is_enabled')->default(true);
            $table->timestamps();

            $table->unique(['tenant_id', 'item_id', 'module_code'], 'item_usage_rules_item_module_uk');
            $table->index(['tenant_id', 'organization_unit_id'], 'item_usage_rules_tenant_org_ix');
            $table->index('item_id', 'item_usage_rules_item_ix');

            $table->unique(['id', 'tenant_id'], 'item_usage_rules_id_tenant_uk');
            $table->foreign(['organization_unit_id', 'tenant_id'], 'item_usage_rules_organization_unit_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('organization_units')
                ->restrictOnDelete();
            $table->foreign(['item_id', 'tenant_id'], 'item_usage_rules_item_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('items')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('item_usage_rules');
    }
};
