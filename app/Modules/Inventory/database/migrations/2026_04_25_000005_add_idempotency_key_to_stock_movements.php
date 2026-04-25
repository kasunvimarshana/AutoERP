<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('stock_movements') && ! Schema::hasColumn('stock_movements', 'idempotency_key')) {
            Schema::table('stock_movements', function (Blueprint $table): void {
                $table->string('idempotency_key', 128)->nullable()->after('metadata');
                $table->unique(['tenant_id', 'idempotency_key'], 'stock_movements_tenant_idempotency_key_uk');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('stock_movements') && Schema::hasColumn('stock_movements', 'idempotency_key')) {
            Schema::table('stock_movements', function (Blueprint $table): void {
                $table->dropUnique('stock_movements_tenant_idempotency_key_uk');
                $table->dropColumn('idempotency_key');
            });
        }
    }
};
