<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('uom_conversions', function (Blueprint $table): void {
            $table->foreignId('product_id')
                ->nullable()
                ->after('tenant_id')
                ->constrained('products', 'id', 'uom_conversions_product_id_fk')
                ->cascadeOnDelete();
            $table->boolean('is_bidirectional')->default(true)->after('factor');
            $table->boolean('is_active')->default(true)->after('is_bidirectional');
        });

        Schema::table('uom_conversions', function (Blueprint $table): void {
            $table->dropUnique('uom_conversions_tenant_from_to_uk');
            $table->unique(['tenant_id', 'product_id', 'from_uom_id', 'to_uom_id'], 'uom_conversions_scope_from_to_uk');
            $table->index(['tenant_id', 'product_id', 'is_active'], 'uom_conversions_scope_active_idx');
        });
    }

    public function down(): void
    {
        Schema::table('uom_conversions', function (Blueprint $table): void {
            $table->dropUnique('uom_conversions_scope_from_to_uk');
            $table->dropIndex('uom_conversions_scope_active_idx');
            $table->unique(['tenant_id', 'from_uom_id', 'to_uom_id'], 'uom_conversions_tenant_from_to_uk');
            $table->dropColumn('is_active');
            $table->dropColumn('is_bidirectional');
            $table->dropConstrainedForeignId('product_id');
        });
    }
};
