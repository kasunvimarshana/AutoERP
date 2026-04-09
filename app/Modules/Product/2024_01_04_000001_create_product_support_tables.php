<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── Categories (hierarchical tree) ────────────────────────────────────
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->string('name');
            $table->string('slug')->unique();
            $table->unsignedTinyInteger('level')->default(1);
            $table->string('path', 1000);
            $table->string('image_url', 500)->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('parent_id')->references('id')->on('categories')->nullOnDelete();

            $table->index(['tenant_id', 'parent_id']);
            $table->index('path');
        });

        // ── Brands ───────────────────────────────────────────────────────────
        Schema::create('brands', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->string('name');
            $table->string('code', 50)->nullable();
            $table->string('logo_url', 500)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->unique(['tenant_id', 'name']);
        });

        // ── Units of Measure ─────────────────────────────────────────────────
        Schema::create('units_of_measure', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->string('name', 100);
            $table->string('symbol', 20);
            $table->enum('type', ['quantity', 'weight', 'volume', 'length', 'area', 'time', 'other']);
            $table->boolean('is_base')->default(false); // base unit in its type group
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->unique(['tenant_id', 'symbol']);
        });

        // ── UoM Conversions ──────────────────────────────────────────────────
        Schema::create('uom_conversions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('from_uom_id');
            $table->unsignedBigInteger('to_uom_id');
            $table->decimal('factor', 20, 8);       // multiply from_qty by factor to get to_qty
            $table->unsignedBigInteger('product_id')->nullable(); // null = global; set = product-specific
            $table->timestamps();

            $table->foreign('from_uom_id')->references('id')->on('units_of_measure')->cascadeOnDelete();
            $table->foreign('to_uom_id')->references('id')->on('units_of_measure')->cascadeOnDelete();

            $table->index(['from_uom_id', 'to_uom_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('uom_conversions');
        Schema::dropIfExists('units_of_measure');
        Schema::dropIfExists('brands');
        Schema::dropIfExists('categories');
    }
};
