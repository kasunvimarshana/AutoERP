<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('purchase_returns', 'currency_id')) {
            return;
        }

        // Do not rewrite amounts or guess currency on pre-upgrade returns.
        Schema::table('purchase_returns', function (Blueprint $table): void {
            $table->unsignedBigInteger('currency_id')->nullable();
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('purchase_returns', 'currency_id')) {
            return;
        }

        Schema::table('purchase_returns', function (Blueprint $table): void {
            $table->dropColumn('currency_id');
        });
    }
};
