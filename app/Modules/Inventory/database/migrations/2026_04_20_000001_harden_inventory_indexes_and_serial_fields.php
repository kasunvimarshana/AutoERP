<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('serials') && ! Schema::hasColumn('serials', 'manufacture_date')) {
            Schema::table('serials', function (Blueprint $table): void {
                $table->date('manufacture_date')->nullable();
            });
        }

        if (Schema::hasTable('stock_levels')) {
            Schema::table('stock_levels', function (Blueprint $table): void {
                $table->index(['tenant_id', 'location_id', 'product_id'], 'stock_levels_tenant_location_product_idx');
            });
        }

        if (Schema::hasTable('stock_movements')) {
            Schema::table('stock_movements', function (Blueprint $table): void {
                $table->index(['tenant_id', 'from_location_id', 'performed_at'], 'stock_movements_tenant_from_loc_date_idx');
                $table->index(['tenant_id', 'to_location_id', 'performed_at'], 'stock_movements_tenant_to_loc_date_idx');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('stock_movements')) {
            Schema::table('stock_movements', function (Blueprint $table): void {
                $table->dropIndex('stock_movements_tenant_from_loc_date_idx');
                $table->dropIndex('stock_movements_tenant_to_loc_date_idx');
            });
        }

        if (Schema::hasTable('stock_levels')) {
            Schema::table('stock_levels', function (Blueprint $table): void {
                $table->dropIndex('stock_levels_tenant_location_product_idx');
            });
        }

        if (Schema::hasTable('serials') && Schema::hasColumn('serials', 'manufacture_date')) {
            Schema::table('serials', function (Blueprint $table): void {
                $table->dropColumn('manufacture_date');
            });
        }
    }
};
