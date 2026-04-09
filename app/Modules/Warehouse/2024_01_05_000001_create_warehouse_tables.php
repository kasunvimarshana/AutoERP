<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── Warehouses ────────────────────────────────────────────────────────
        Schema::create('warehouses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('org_unit_id')->nullable();
            $table->string('code', 30);
            $table->string('name');
            $table->enum('type', ['main', 'transit', 'virtual', 'return', 'quarantine'])->default('main');
            $table->string('address_line1')->nullable();
            $table->string('city', 100)->nullable();
            $table->char('country_code', 2)->nullable();
            $table->boolean('is_active')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('org_unit_id')->references('id')->on('organizations')->nullOnDelete();

            $table->unique(['tenant_id', 'code']);
        });

        // ── Locations (hierarchical: zone → aisle → rack → shelf → bin) ──────
        Schema::create('locations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('warehouse_id');
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->string('code', 50);
            $table->string('name');
            $table->enum('type', ['zone', 'aisle', 'rack', 'shelf', 'bin', 'floor', 'other'])->default('bin');
            $table->unsignedTinyInteger('level')->default(1);
            $table->string('path', 1000);
            $table->decimal('capacity', 18, 4)->nullable();
            $table->boolean('is_pickable')->default(true);
            $table->boolean('is_receivable')->default(true);
            $table->boolean('is_active')->default(true);
            $table->string('barcode', 100)->nullable(); // location barcode for scanning
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('warehouse_id')->references('id')->on('warehouses')->cascadeOnDelete();
            $table->foreign('parent_id')->references('id')->on('locations')->nullOnDelete();

            $table->unique(['tenant_id', 'code']);
            $table->index(['warehouse_id', 'parent_id']);
            $table->index('path');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('locations');
        Schema::dropIfExists('warehouses');
    }
};
