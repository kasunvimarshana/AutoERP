<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            // buying_uom: UOM used when purchasing this product (e.g. "box").
            // Falls back to the inventory UOM (uom column) when null.
            $table->string('buying_uom', 50)->nullable()->after('uom');

            // selling_uom: UOM used when selling this product (e.g. "pack").
            // Falls back to the inventory UOM (uom column) when null.
            $table->string('selling_uom', 50)->nullable()->after('buying_uom');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->dropColumn(['buying_uom', 'selling_uom']);
        });
    }
};
