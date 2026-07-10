<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const TABLE = 'hr_employees';

    private const ROW_VERSION_COLUMN = 'row_version';

    public function up(): void
    {
        if (Schema::hasColumn(self::TABLE, self::ROW_VERSION_COLUMN)) {
            return;
        }

        Schema::table(self::TABLE, function (Blueprint $table): void {
            $table->unsignedBigInteger(self::ROW_VERSION_COLUMN)
                ->default(1)
                ->after('id');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn(self::TABLE, self::ROW_VERSION_COLUMN)) {
            return;
        }

        Schema::table(self::TABLE, function (Blueprint $table): void {
            $table->dropColumn(self::ROW_VERSION_COLUMN);
        });
    }
};
