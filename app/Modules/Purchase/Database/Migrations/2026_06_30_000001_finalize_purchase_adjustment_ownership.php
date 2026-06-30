<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const TABLE = 'purchase_header_adjustments';

    /** @var list<string> */
    private const FINANCE_IDENTITY_COLUMNS = [
        'finance_posting_profile_id',
        'finance_account_id',
    ];

    public function up(): void
    {
        foreach (self::FINANCE_IDENTITY_COLUMNS as $column) {
            $this->dropForeignKeysForColumn($column);
        }

        Schema::table(self::TABLE, function (Blueprint $table): void {
            $table->dropColumn([
                'finance_posting_profile_id',
                'finance_account_id',
                'override_reason',
            ]);
            $table->renameColumn('mapping_source', 'recognition_source');
        });
    }

    public function down(): void
    {
        Schema::table(self::TABLE, function (Blueprint $table): void {
            $table->renameColumn('recognition_source', 'mapping_source');
            $table->unsignedBigInteger('finance_posting_profile_id')->nullable();
            $table->unsignedBigInteger('finance_account_id')->nullable();
            $table->text('override_reason')->nullable();
        });
    }

    private function dropForeignKeysForColumn(string $column): void
    {
        $constraints = DB::select(
            <<<'SQL'
                SELECT CONSTRAINT_NAME AS constraint_name
                FROM information_schema.KEY_COLUMN_USAGE
                WHERE TABLE_SCHEMA = DATABASE()
                  AND TABLE_NAME = ?
                  AND COLUMN_NAME = ?
                  AND REFERENCED_TABLE_NAME IS NOT NULL
            SQL,
            [self::TABLE, $column],
        );

        foreach ($constraints as $constraint) {
            $name = (string) $constraint->constraint_name;
            DB::statement(sprintf(
                'ALTER TABLE `%s` DROP FOREIGN KEY `%s`',
                self::TABLE,
                str_replace('`', '``', $name),
            ));
        }
    }
};
