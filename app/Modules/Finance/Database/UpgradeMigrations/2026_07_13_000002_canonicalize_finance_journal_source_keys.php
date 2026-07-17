<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const TABLE = 'finance_journal_entries';

    public function up(): void
    {
        $this->recalculate(false);
    }

    public function down(): void
    {
        $this->recalculate(true);
    }

    private function recalculate(bool $includeModule): void
    {
        DB::transaction(function () use ($includeModule): void {
            $journals = DB::table(self::TABLE)
                ->whereNotNull('source_key')
                ->orderBy('id')
                ->lockForUpdate()
                ->get([
                    'id',
                    'tenant_id',
                    'organization_unit_id',
                    'source_module',
                    'source_type',
                    'source_id',
                ]);

            $keys = [];
            foreach ($journals as $journal) {
                $sourceType = trim((string) $journal->source_type);
                $sourceId = (int) $journal->source_id;
                if ($sourceType === '' || $sourceId < 1) {
                    throw new RuntimeException(
                        'Finance journal source key cannot be migrated because its source identity is incomplete.',
                    );
                }

                $identity = [
                    'tenant_id' => (int) $journal->tenant_id,
                    'organization_unit_id' => $journal->organization_unit_id === null
                        ? null
                        : (int) $journal->organization_unit_id,
                ];
                if ($includeModule) {
                    $identity['source_module'] = $journal->source_module;
                }
                $identity['source_type'] = $sourceType;
                $identity['source_id'] = $sourceId;
                $key = hash('sha256', json_encode($identity, JSON_THROW_ON_ERROR));

                if (isset($keys[$key])) {
                    throw new RuntimeException(
                        'Multiple Finance journals use the same canonical source identity: journal IDs '
                        .$keys[$key].' and '.(int) $journal->id.'.',
                    );
                }
                $keys[$key] = (int) $journal->id;
            }

            if ($keys === []) {
                return;
            }

            DB::table(self::TABLE)
                ->whereIn('id', array_values($keys))
                ->update(['source_key' => null]);

            foreach ($keys as $key => $journalId) {
                DB::table(self::TABLE)
                    ->where('id', $journalId)
                    ->update(['source_key' => $key]);
            }
        });
    }
};
