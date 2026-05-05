<?php

declare(strict_types=1);

namespace Modules\Finance\Infrastructure\Listeners\Concerns;

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

trait HandlesReplayConflicts
{
    protected function journalAlreadyPosted(int $tenantId, string $referenceType, int $referenceId): bool
    {
        return DB::table('journal_entries')
            ->where('tenant_id', $tenantId)
            ->where('reference_type', $referenceType)
            ->where('reference_id', $referenceId)
            ->exists();
    }

    protected function artifactsAlreadyPosted(int $tenantId, string $referenceType, int $referenceId, string $transactionTable): bool
    {
        $journalExists = $this->journalAlreadyPosted($tenantId, $referenceType, $referenceId);

        $transactionExists = DB::table($transactionTable)
            ->where('tenant_id', $tenantId)
            ->where('reference_type', $referenceType)
            ->where('reference_id', $referenceId)
            ->exists();

        return $journalExists && $transactionExists;
    }

    /**
     * @param array<int, string> $additionalNeedles
     */
    protected function isReplayConflict(QueryException $exception, array $additionalNeedles = []): bool
    {
        $message = strtolower($exception->getMessage());

        if (! str_contains($message, 'duplicate') && ! str_contains($message, 'unique')) {
            return false;
        }

        $needles = array_merge([
            'journal_entries_tenant_reference_uk',
            'journal_entries.tenant_id, journal_entries.reference_type, journal_entries.reference_id',
        ], array_map('strtolower', $additionalNeedles));

        foreach ($needles as $needle) {
            if (str_contains($message, $needle)) {
                return true;
            }
        }

        return false;
    }
}
