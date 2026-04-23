<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('serials', function (Blueprint $table): void {
            if (! Schema::hasColumn('serials', 'sold_at')) {
                $table->timestamp('sold_at')->nullable()->after('status');
            }
            if (! Schema::hasColumn('serials', 'metadata')) {
                $table->json('metadata')->nullable()->after('notes');
            }
            if (! Schema::hasColumn('serials', 'deleted_at')) {
                $table->softDeletes();
            }
            if (! Schema::hasIndex('serials', 'serials_tenant_product_status_idx')) {
                $table->index(['tenant_id', 'product_id', 'status'], 'serials_tenant_product_status_idx');
            }
        });
    }

    public function down(): void
    {
        Schema::table('serials', function (Blueprint $table): void {
            $table->dropIndex('serials_tenant_product_status_idx');
            if (Schema::hasColumn('serials', 'sold_at')) {
                $table->dropColumn('sold_at');
            }
            if (Schema::hasColumn('serials', 'metadata')) {
                $table->dropColumn('metadata');
            }
            if (Schema::hasColumn('serials', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
        });
    }
};
