<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tax Rates
        Schema::create('tax_rates', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->decimal('rate', 8, 4)->default(0); // percentage rate e.g. 10.0000
            $table->string('type')->default('simple'); // simple, compound, group
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'name']);
            $table->index(['tenant_id', 'is_active']);
        });

        // Group sub-taxes (many-to-many for group tax rates)
        Schema::create('tax_rate_sub_taxes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tax_rate_id')->constrained('tax_rates')->cascadeOnDelete();
            $table->foreignUuid('sub_tax_id')->constrained('tax_rates')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['tax_rate_id', 'sub_tax_id']);
        });

        // Brands
        Schema::create('brands', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->string('logo')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'slug']);
        });

        // Customer Groups
        Schema::create('customer_groups', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->decimal('discount_percent', 8, 4)->default(0); // default discount %
            $table->json('pricing_overrides')->nullable(); // {product_id: price}
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_groups');
        Schema::dropIfExists('brands');
        Schema::dropIfExists('tax_rate_sub_taxes');
        Schema::dropIfExists('tax_rates');
    }
};
