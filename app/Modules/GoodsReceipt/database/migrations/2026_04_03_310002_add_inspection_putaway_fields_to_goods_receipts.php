<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('goods_receipts', function (Blueprint $table) {
            $table->unsignedBigInteger('put_away_by')->nullable()->after('approved_at');
            $table->unsignedBigInteger('inspected_by')->nullable()->after('put_away_by');
            $table->timestamp('inspected_at')->nullable()->after('inspected_by');
        });
    }

    public function down(): void
    {
        Schema::table('goods_receipts', function (Blueprint $table) {
            $table->dropColumn(['put_away_by', 'inspected_by', 'inspected_at']);
        });
    }
};
