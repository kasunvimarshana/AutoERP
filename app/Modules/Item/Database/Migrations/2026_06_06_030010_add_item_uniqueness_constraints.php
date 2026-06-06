<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('items', function (Blueprint $table): void {
            $table->unique(['tenant_id', 'sku'], 'items_tenant_sku_uk');
            $table->unique(['tenant_id', 'barcode'], 'items_tenant_barcode_uk');
        });

        Schema::table('item_units', function (Blueprint $table): void {
            $table->unique(['item_id', 'uom_id', 'unit_role'], 'item_units_item_uom_role_uk');
        });

        Schema::table('item_codes', function (Blueprint $table): void {
            $table->unique(['tenant_id', 'code_type', 'code'], 'item_codes_tenant_type_code_uk');
        });
    }

    public function down(): void
    {
        Schema::table('item_codes', function (Blueprint $table): void {
            $table->dropUnique('item_codes_tenant_type_code_uk');
        });

        Schema::table('item_units', function (Blueprint $table): void {
            $table->dropUnique('item_units_item_uom_role_uk');
        });

        Schema::table('items', function (Blueprint $table): void {
            $table->dropUnique('items_tenant_sku_uk');
            $table->dropUnique('items_tenant_barcode_uk');
        });
    }
};
