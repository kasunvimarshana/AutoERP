<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('item_units', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units')->nullOnDelete();
            $table->foreignId('item_id')->constrained('items')->cascadeOnDelete();
            $table->foreignId('uom_id')->constrained('unit_of_measures')->restrictOnDelete();
            $table->string('unit_role', 30);
            $table->decimal('conversion_factor', 20, 6)->default('1.000000');
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['tenant_id', 'organization_unit_id'], 'item_units_tenant_org_idx');
            $table->index('item_id', 'item_units_item_idx');
            $table->index('uom_id', 'item_units_uom_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('item_units');
    }
};
