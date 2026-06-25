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
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units')->nullOnDelete();
            $table->foreignId('item_id')->constrained('items')->cascadeOnDelete();
            $table->string('module_code', 50);
            $table->boolean('is_enabled')->default(true);
            $table->timestamps();

            $table->unique(['tenant_id', 'item_id', 'module_code'], 'item_usage_rules_item_module_uk');
            $table->index(['tenant_id', 'organization_unit_id'], 'item_usage_rules_tenant_org_idx');
            $table->index('item_id', 'item_usage_rules_item_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('item_usage_rules');
    }
};
