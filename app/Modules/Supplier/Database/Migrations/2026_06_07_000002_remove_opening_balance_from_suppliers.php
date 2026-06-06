<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('suppliers', 'opening_balance')) {
            Schema::table('suppliers', function (Blueprint $table): void {
                $table->dropColumn('opening_balance');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('suppliers', 'opening_balance')) {
            Schema::table('suppliers', function (Blueprint $table): void {
                $table->decimal('opening_balance', 20, 6)->default('0.000000');
            });
        }
    }
};
