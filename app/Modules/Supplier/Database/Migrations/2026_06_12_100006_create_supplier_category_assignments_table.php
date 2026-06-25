<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supplier_category_assignments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units')->nullOnDelete();
            $table->foreignId('supplier_id')->constrained('suppliers')->cascadeOnDelete();
            $table->foreignId('supplier_category_id')->constrained('supplier_categories')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['supplier_id', 'supplier_category_id'], 'supplier_category_assignments_pair_uk');
            $table->index(['tenant_id', 'organization_unit_id'], 'supplier_category_assignments_tenant_org_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_category_assignments');
    }
};
