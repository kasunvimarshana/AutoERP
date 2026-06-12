<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('item_bundles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units')->nullOnDelete();
            $table->foreignId('parent_item_id')->constrained('items')->cascadeOnDelete();
            $table->foreignId('child_item_id')->constrained('items')->restrictOnDelete();
            $table->foreignId('child_variant_id')->nullable()->constrained('item_variants')->nullOnDelete();
            $table->decimal('quantity', 20, 6);
            $table->foreignId('uom_id')->nullable()->constrained('unit_of_measures')->nullOnDelete();
            $table->string('line_type', 30);
            $table->boolean('is_required')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['tenant_id', 'organization_unit_id'], 'item_bundles_tenant_org_idx');
            $table->index('parent_item_id', 'item_bundles_parent_idx');
            $table->index('child_item_id', 'item_bundles_child_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('item_bundles');
    }
};
