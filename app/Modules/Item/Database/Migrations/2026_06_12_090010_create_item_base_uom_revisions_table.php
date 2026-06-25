<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('item_base_uom_revisions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable();
            $table->foreignId('item_id');
            $table->foreignId('old_base_uom_id');
            $table->foreignId('new_base_uom_id');
            $table->decimal('conversion_factor', 20, 6);
            $table->timestamp('effective_at');
            $table->text('reason')->nullable();
            $table->string('status', 20)->default('draft');
            $table->json('validation_summary')->nullable();
            $table->foreignId('created_by')->nullable();
            $table->foreignId('applied_by')->nullable();
            $table->timestamp('applied_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'organization_unit_id'], 'item_base_uom_revisions_scope_idx');
            $table->index(['item_id', 'effective_at'], 'item_base_uom_revisions_item_effective_idx');
            $table->index('status', 'item_base_uom_revisions_status_idx');

            $table->unique(['id', 'tenant_id'], 'item_base_uom_revisions_id_tenant_uk');
            $table->foreign(['organization_unit_id', 'tenant_id'], 'item_base_uom_revisions_organization_unit_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('organization_units')
                ->restrictOnDelete();
            $table->foreign(['item_id', 'tenant_id'], 'item_base_uom_revisions_item_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('items')
                ->cascadeOnDelete();
            $table->foreign(['old_base_uom_id', 'tenant_id'], 'item_base_uom_revisions_old_base_uom_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('unit_of_measures')
                ->restrictOnDelete();
            $table->foreign(['new_base_uom_id', 'tenant_id'], 'item_base_uom_revisions_new_base_uom_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('unit_of_measures')
                ->restrictOnDelete();
            $table->foreign(['created_by', 'tenant_id'], 'item_base_uom_revisions_created_by_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('users')
                ->restrictOnDelete();
            $table->foreign(['applied_by', 'tenant_id'], 'item_base_uom_revisions_applied_by_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('users')
                ->restrictOnDelete();
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('item_base_uom_revisions');
    }
};
