<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('batches', function (Blueprint $table): void {
            if (! Schema::hasColumn('batches', 'quantity')) {
                $table->decimal('quantity', 20, 6)->default(0)->after('expiry_date');
            }
            if (! Schema::hasColumn('batches', 'deleted_at')) {
                $table->softDeletes();
            }
            if (! Schema::hasIndex('batches', 'batches_tenant_id_expiry_date_idx')) {
                $table->index(['tenant_id', 'expiry_date'], 'batches_tenant_id_expiry_date_idx');
            }
            if (! Schema::hasIndex('batches', 'batches_tenant_product_status_idx')) {
                $table->index(['tenant_id', 'product_id', 'status'], 'batches_tenant_product_status_idx');
            }
        });
    }

    public function down(): void
    {
        Schema::table('batches', function (Blueprint $table): void {
            $table->dropIndex('batches_tenant_id_expiry_date_idx');
            $table->dropIndex('batches_tenant_product_status_idx');
            if (Schema::hasColumn('batches', 'quantity')) {
                $table->dropColumn('quantity');
            }
            if (Schema::hasColumn('batches', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
        });
    }
};
