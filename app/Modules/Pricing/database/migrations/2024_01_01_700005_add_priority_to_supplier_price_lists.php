<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('supplier_price_lists', 'priority')) {
            Schema::table('supplier_price_lists', function (Blueprint $table): void {
                $table->unsignedInteger('priority')->default(0)->after('price_list_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('supplier_price_lists', 'priority')) {
            Schema::table('supplier_price_lists', function (Blueprint $table): void {
                $table->dropColumn('priority');
            });
        }
    }
};
