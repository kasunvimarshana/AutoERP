<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Categories table
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('parent_id')->nullable()->constrained('categories')->onDelete('cascade');
            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->string('image')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'slug']);
        });

        // Brands table
        Schema::create('brands', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->string('logo')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'slug']);
        });

        // Units of Measure table
        Schema::create('units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('symbol');
            $table->string('type'); // weight, length, volume, quantity, etc.
            $table->decimal('conversion_factor', 15, 6)->default(1); // Conversion to base unit
            $table->foreignId('base_unit_id')->nullable()->constrained('units')->onDelete('set null');
            $table->boolean('is_base')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        // Taxes table
        Schema::create('taxes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('type'); // percentage, fixed
            $table->decimal('rate', 8, 4); // Tax rate (percentage or fixed amount)
            $table->text('description')->nullable();
            $table->boolean('is_inclusive')->default(false); // Tax included in price
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        // Warehouses/Locations table
        Schema::create('warehouses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('code')->unique();
            $table->string('type')->default('warehouse'); // warehouse, store, showroom
            $table->text('address')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('country')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('contact_person')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->boolean('is_active')->default(true);
            $table->json('settings')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // Location/Bin within warehouse
        Schema::create('locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_id')->constrained()->onDelete('cascade');
            $table->string('name'); // e.g., Aisle A, Rack 1, Bin 5
            $table->string('code');
            $table->string('type')->nullable(); // aisle, rack, bin, shelf
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['warehouse_id', 'code']);
        });

        // Currencies table
        Schema::create('currencies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('code', 3)->unique(); // ISO 4217 code (USD, EUR, etc.)
            $table->string('symbol');
            $table->decimal('exchange_rate', 15, 6)->default(1);
            $table->boolean('is_base')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('locations');
        Schema::dropIfExists('warehouses');
        Schema::dropIfExists('currencies');
        Schema::dropIfExists('taxes');
        Schema::dropIfExists('units');
        Schema::dropIfExists('brands');
        Schema::dropIfExists('categories');
    }
};
