<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── Product Brands ─────────────────────────────────────────────────
        Schema::create('product_brands', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id')->nullable()->index();
            $table->unsignedBigInteger('organization_id')->nullable()->index();
            $table->string('code', 50)->nullable();
            $table->string('name', 150);
            $table->string('slug', 200)->nullable();
            $table->text('description')->nullable();
            $table->string('logo_path')->nullable();
            $table->string('website')->nullable();
            $table->string('country_of_origin', 10)->nullable();
            $table->json('metadata')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // ── Attribute Sets (e.g. "Clothing", "Electronics", "Pharma") ──────
        Schema::create('product_attribute_sets', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id')->nullable()->index();
            $table->string('name', 100);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // ── Attributes (e.g. Color, Size, Material, Potency, Voltage) ──────
        Schema::create('product_attributes', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id')->nullable()->index();
            $table->string('code', 50);
            $table->string('name', 100);
            $table->string('input_type', 30)->default('select');
            // select | multiselect | text | textarea | boolean | date | number | swatch
            $table->boolean('is_variant_attribute')->default(false); // Used to generate variants
            $table->boolean('is_filterable')->default(false);
            $table->boolean('is_searchable')->default(false);
            $table->boolean('is_required')->default(false);
            $table->json('validation_rules')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['tenant_id', 'code']);
        });

        // ── Attribute Values (options for select/swatch attributes) ─────────
        Schema::create('product_attribute_values', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('attribute_id');
            $table->string('label', 100);
            $table->string('value', 100);  // Internal value
            $table->string('color_hex', 10)->nullable();  // For swatch
            $table->string('image_path')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->foreign('attribute_id')->references('id')->on('product_attributes')->cascadeOnDelete();
        });

        // ── Attribute Set ↔ Attribute pivot ─────────────────────────────────
        Schema::create('attribute_set_attributes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('attribute_set_id');
            $table->unsignedBigInteger('attribute_id');
            $table->string('group_name', 100)->nullable(); // Group within set
            $table->integer('sort_order')->default(0);
            $table->boolean('is_required')->default(false);
            $table->timestamps();
            $table->foreign('attribute_set_id')->references('id')->on('product_attribute_sets')->cascadeOnDelete();
            $table->foreign('attribute_id')->references('id')->on('product_attributes')->cascadeOnDelete();
            $table->unique(['attribute_set_id', 'attribute_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attribute_set_attributes');
        Schema::dropIfExists('product_attribute_values');
        Schema::dropIfExists('product_attributes');
        Schema::dropIfExists('product_attribute_sets');
        Schema::dropIfExists('product_brands');
    }
};
