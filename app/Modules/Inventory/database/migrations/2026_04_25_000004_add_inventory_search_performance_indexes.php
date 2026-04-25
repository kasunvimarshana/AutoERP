<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('batches')) {
            Schema::table('batches', function (Blueprint $table): void {
                $table->index(['tenant_id', 'batch_number'], 'batches_tenant_batch_number_idx');
                $table->index(['tenant_id', 'lot_number'], 'batches_tenant_lot_number_idx');
            });
        }

        if (Schema::hasTable('serials')) {
            Schema::table('serials', function (Blueprint $table): void {
                $table->index(['tenant_id', 'product_id', 'variant_id'], 'serials_tenant_product_variant_idx');
                $table->index(['tenant_id', 'batch_id'], 'serials_tenant_batch_idx');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('serials')) {
            Schema::table('serials', function (Blueprint $table): void {
                $table->dropIndex('serials_tenant_product_variant_idx');
                $table->dropIndex('serials_tenant_batch_idx');
            });
        }

        if (Schema::hasTable('batches')) {
            Schema::table('batches', function (Blueprint $table): void {
                $table->dropIndex('batches_tenant_batch_number_idx');
                $table->dropIndex('batches_tenant_lot_number_idx');
            });
        }
    }
};
