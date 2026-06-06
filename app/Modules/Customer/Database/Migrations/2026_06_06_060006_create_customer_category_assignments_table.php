<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_category_assignments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units')->nullOnDelete();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->foreignId('customer_category_id')->constrained('customer_categories')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['customer_id', 'customer_category_id'], 'customer_category_assignments_pair_uk');
            $table->index(['tenant_id', 'organization_unit_id'], 'customer_category_assignments_tenant_org_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_category_assignments');
    }
};
