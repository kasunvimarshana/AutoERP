<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $duplicates = DB::table('products')
            ->select('tenant_id', 'slug', DB::raw('COUNT(*) as duplicate_count'))
            ->groupBy('tenant_id', 'slug')
            ->havingRaw('COUNT(*) > 1')
            ->limit(20)
            ->get();

        if ($duplicates->isNotEmpty()) {
            $duplicateSummary = $duplicates
                ->map(
                    static fn (object $row): string => sprintf(
                        'tenant_id=%d, slug=%s, count=%d',
                        (int) $row->tenant_id,
                        (string) $row->slug,
                        (int) $row->duplicate_count
                    )
                )
                ->implode('; ');

            throw new \RuntimeException(
                'Cannot add products_tenant_slug_uk because duplicate tenant/slug pairs already exist. '
                .'Resolve duplicates first. Examples: '.$duplicateSummary
            );
        }

        Schema::table('products', function (Blueprint $table): void {
            $table->unique(['tenant_id', 'slug'], 'products_tenant_slug_uk');
            $table->index(['tenant_id', 'is_active'], 'products_tenant_active_idx');
            $table->index(['tenant_id', 'name'], 'products_tenant_name_idx');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->dropUnique('products_tenant_slug_uk');
            $table->dropIndex('products_tenant_active_idx');
            $table->dropIndex('products_tenant_name_idx');
        });
    }
};
