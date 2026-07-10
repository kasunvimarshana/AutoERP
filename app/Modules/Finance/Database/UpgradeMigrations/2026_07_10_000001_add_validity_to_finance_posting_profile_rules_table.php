<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const TABLE = 'finance_posting_profile_rules';
    private const OPENING_EFFECTIVE_DATE = '1900-01-01';
    private const OLD_PROFILE_KEY_UNIQUE = 'finance_posting_profile_rules_profile_key_uk';
    private const PROFILE_KEY_FROM_UNIQUE = 'finance_posting_rules_profile_key_from_uk';
    private const EFFECTIVE_LOOKUP_INDEX = 'finance_posting_rules_effective_lookup_idx';

    public function up(): void
    {
        if (! Schema::hasColumn(self::TABLE, 'effective_from')) {
            Schema::table(self::TABLE, function (Blueprint $table): void {
                $table->date('effective_from')
                    ->default(self::OPENING_EFFECTIVE_DATE)
                    ->after('account_role_id');
            });
        }

        if (! Schema::hasColumn(self::TABLE, 'effective_to')) {
            Schema::table(self::TABLE, function (Blueprint $table): void {
                $table->date('effective_to')
                    ->nullable()
                    ->after('effective_from');
            });
        }

        if (! Schema::hasColumn(self::TABLE, 'is_active')) {
            Schema::table(self::TABLE, function (Blueprint $table): void {
                $table->boolean('is_active')
                    ->default(true)
                    ->after('effective_to');
            });
        }

        if (Schema::hasIndex(self::TABLE, self::OLD_PROFILE_KEY_UNIQUE, 'unique')) {
            Schema::table(self::TABLE, function (Blueprint $table): void {
                $table->dropUnique(self::OLD_PROFILE_KEY_UNIQUE);
            });
        }

        if (! Schema::hasIndex(self::TABLE, self::PROFILE_KEY_FROM_UNIQUE, 'unique')) {
            Schema::table(self::TABLE, function (Blueprint $table): void {
                $table->unique(
                    ['posting_profile_id', 'line_key', 'effective_from'],
                    self::PROFILE_KEY_FROM_UNIQUE,
                );
            });
        }

        if (! Schema::hasIndex(self::TABLE, self::EFFECTIVE_LOOKUP_INDEX)) {
            Schema::table(self::TABLE, function (Blueprint $table): void {
                $table->index(
                    ['posting_profile_id', 'line_key', 'effective_from', 'effective_to', 'is_active'],
                    self::EFFECTIVE_LOOKUP_INDEX,
                );
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasIndex(self::TABLE, self::EFFECTIVE_LOOKUP_INDEX)) {
            Schema::table(self::TABLE, function (Blueprint $table): void {
                $table->dropIndex(self::EFFECTIVE_LOOKUP_INDEX);
            });
        }

        if (Schema::hasIndex(self::TABLE, self::PROFILE_KEY_FROM_UNIQUE, 'unique')) {
            Schema::table(self::TABLE, function (Blueprint $table): void {
                $table->dropUnique(self::PROFILE_KEY_FROM_UNIQUE);
            });
        }

        if (! Schema::hasIndex(self::TABLE, self::OLD_PROFILE_KEY_UNIQUE, 'unique')) {
            Schema::table(self::TABLE, function (Blueprint $table): void {
                $table->unique(
                    ['posting_profile_id', 'line_key'],
                    self::OLD_PROFILE_KEY_UNIQUE,
                );
            });
        }

        Schema::table(self::TABLE, function (Blueprint $table): void {
            if (Schema::hasColumn(self::TABLE, 'is_active')) {
                $table->dropColumn('is_active');
            }
            if (Schema::hasColumn(self::TABLE, 'effective_to')) {
                $table->dropColumn('effective_to');
            }
            if (Schema::hasColumn(self::TABLE, 'effective_from')) {
                $table->dropColumn('effective_from');
            }
        });
    }
};
