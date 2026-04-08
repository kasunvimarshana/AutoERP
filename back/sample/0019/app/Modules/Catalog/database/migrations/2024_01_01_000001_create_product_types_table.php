<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Product Types: physical, storable, consumable, service, digital,
 * combo/bundle, variable/configurable, rental, subscription, raw_material, etc.
 * Fully dynamic — types drive behavior flags rather than hard-coded logic.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_types', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id')->nullable()->index(); // Multi-tenant
            $table->string('code', 50)->unique();             // e.g. storable, service
            $table->string('name', 100);
            $table->text('description')->nullable();

            // Behavioral flags — drives module logic dynamically
            $table->boolean('is_stockable')->default(false);       // Tracked in inventory
            $table->boolean('is_purchasable')->default(true);
            $table->boolean('is_sellable')->default(true);
            $table->boolean('is_rentable')->default(false);
            $table->boolean('is_manufacturable')->default(false);
            $table->boolean('is_kit')->default(false);             // Combo/bundle
            $table->boolean('is_variable')->default(false);        // Has variants
            $table->boolean('is_service')->default(false);         // Non-physical
            $table->boolean('is_digital')->default(false);         // Digital delivery
            $table->boolean('is_serialized')->default(false);      // Requires serial#
            $table->boolean('is_batch_tracked')->default(false);   // Requires batch/lot
            $table->boolean('is_expiry_tracked')->default(false);  // Tracks expiry date
            $table->boolean('requires_uom_conversion')->default(false);

            // Accounting / tax defaults
            $table->string('costing_method')->nullable();  // Override per product
            $table->json('metadata')->nullable();           // Extensible

            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_types');
    }
};
