<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('warehouses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('organization_id')->nullable()->index();
            $table->string('name');
            $table->string('code')->unique();
            $table->string('type')->default('standard');
            // standard | 3pl | virtual | consignment | dropship | transit | quarantine
            $table->boolean('is_active')->default(true);
            $table->boolean('allows_negative_stock')->default(false);
            $table->string('valuation_method', 30)->nullable();  // overrides tenant setting
            $table->string('stock_rotation', 20)->nullable();    // overrides tenant setting
            $table->string('allocation_algorithm', 30)->nullable();
            $table->json('address')->nullable();
            $table->json('contact')->nullable();
            $table->json('attributes')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'is_active']);
        });

        Schema::create('warehouse_zones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('code');
            $table->string('type')->default('standard');
            // standard | receiving | shipping | quarantine | returns | cold | hazmat | bonded
            $table->boolean('is_pickable')->default(true);
            $table->boolean('is_storable')->default(true);
            $table->boolean('is_receivable')->default(true);
            $table->boolean('is_returnable')->default(true);
            $table->json('attributes')->nullable();  // temperature, humidity, fire_rating…
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['warehouse_id', 'code']);
        });

        Schema::create('storage_locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_zone_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('barcode')->nullable()->unique();
            $table->string('aisle')->nullable();
            $table->string('bay')->nullable();
            $table->string('level')->nullable();
            $table->string('position')->nullable();
            $table->decimal('max_weight', 12, 4)->nullable();
            $table->decimal('max_volume', 12, 4)->nullable();
            $table->json('dimensions')->nullable();  // {l, w, h, unit}
            $table->boolean('is_active')->default(true);
            $table->boolean('allow_mixed_products')->default(true);
            $table->boolean('allow_mixed_lots')->default(true);
            $table->integer('sort_order')->default(0);  // picking sequence
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('storage_locations');
        Schema::dropIfExists('warehouse_zones');
        Schema::dropIfExists('warehouses');
    }
};
