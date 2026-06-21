<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rental_agreement_rate_components', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1);
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units')->nullOnDelete();
            $table->json('metadata')->nullable();
            $table->foreignId('rate_version_id')->constrained('rental_agreement_rate_versions')->cascadeOnDelete();
            $table->foreignId('vehicle_category_id')->nullable()->constrained('vehicle_categories')->nullOnDelete();
            $table->string('component_code', 50);
            $table->string('unit', 30);
            $table->decimal('included_quantity', 20, 6)->default('0.000000');
            $table->decimal('rate', 20, 6)->default('0.000000');
            $table->decimal('multiplier', 20, 6)->default('1.000000');
            $table->decimal('minimum_amount', 20, 6)->nullable();
            $table->decimal('maximum_amount', 20, 6)->nullable();
            $table->foreignId('tax_group_override_id')->nullable()->constrained('tax_groups')->nullOnDelete();
            $table->boolean('is_taxable')->default(true);
            $table->unsignedInteger('calculation_order')->default(0);
            $table->string('status', 20)->default('active');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->unique(['rate_version_id', 'vehicle_category_id', 'component_code'], 'rental_rate_components_version_category_code_uk');
            $table->index(['rate_version_id', 'calculation_order'], 'rental_rate_components_order_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rental_agreement_rate_components');
    }
};
