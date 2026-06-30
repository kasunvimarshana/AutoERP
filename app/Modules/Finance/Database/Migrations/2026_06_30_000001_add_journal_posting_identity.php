<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const TABLE = 'finance_journal_entries';
    private const SOURCE_KEY_UNIQUE = 'finance_journal_entries_source_key_uk';

    public function up(): void
    {
        Schema::table(self::TABLE, function (Blueprint $table): void {
            $table->char('source_key', 64)->nullable()->after('source_date');
            $table->char('posting_fingerprint', 64)->nullable()->after('source_key');
            $table->unique('source_key', self::SOURCE_KEY_UNIQUE);
        });
    }

    public function down(): void
    {
        Schema::table(self::TABLE, function (Blueprint $table): void {
            $table->dropUnique(self::SOURCE_KEY_UNIQUE);
            $table->dropColumn(['source_key', 'posting_fingerprint']);
        });
    }
};
