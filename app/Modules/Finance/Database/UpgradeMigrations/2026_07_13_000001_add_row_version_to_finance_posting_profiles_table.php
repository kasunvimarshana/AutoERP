<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const TABLE = 'finance_posting_profiles';
    private const COLUMN = 'row_version';

    public function up(): void
    {
        if (Schema::hasColumn(self::TABLE, self::COLUMN)) {
            return;
        }

        Schema::table(self::TABLE, function (Blueprint $table): void {
            $table->unsignedBigInteger(self::COLUMN)
                ->default(1)
                ->after('is_active');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn(self::TABLE, self::COLUMN)) {
            return;
        }

        Schema::table(self::TABLE, function (Blueprint $table): void {
            $table->dropColumn(self::COLUMN);
        });
    }
};
