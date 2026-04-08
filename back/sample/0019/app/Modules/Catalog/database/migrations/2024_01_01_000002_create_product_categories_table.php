<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Product Categories — Nested set (parent_id + lft/rgt) or simple adjacency.
 * Supports multi-level hierarchies per tenant/org.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_categories', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id')->nullable()->index();
            $table->unsignedBigInteger('organization_id')->nullable()->index();

            $table->unsignedBigInteger('parent_id')->nullable()->index(); // Self-referential
            $table->string('code', 50)->nullable();
            $table->string('name', 150);
            $table->string('slug', 200)->nullable();
            $table->text('description')->nullable();
            $table->string('image_path')->nullable();

            // Nested-set columns for efficient tree queries
            $table->unsignedInteger('lft')->nullable()->index();
            $table->unsignedInteger('rgt')->nullable()->index();
            $table->unsignedInteger('depth')->default(0);

            // Accounting defaults per category (overridable per product)
            $table->unsignedBigInteger('income_account_id')->nullable();
            $table->unsignedBigInteger('expense_account_id')->nullable();
            $table->unsignedBigInteger('inventory_account_id')->nullable();
            $table->unsignedBigInteger('cogs_account_id')->nullable();
            $table->unsignedBigInteger('default_tax_group_id')->nullable();

            // Industry-specific classification codes
            $table->string('gs1_gpc_code', 20)->nullable();   // GS1 Global Product Classification
            $table->string('hs_code', 20)->nullable();         // Harmonized System (customs)
            $table->string('unspsc_code', 20)->nullable();     // UNSPSC classification
            $table->json('custom_attributes')->nullable();

            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('parent_id')->references('id')->on('product_categories')->nullOnDelete();
            $table->unique(['tenant_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_categories');
    }
};
