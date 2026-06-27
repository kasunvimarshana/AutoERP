<?php

declare(strict_types=1);

namespace Modules\Finance\Services;

use JsonException;
use Modules\Finance\DTOs\CreateJournalEntryData;
use Modules\Finance\DTOs\PostingSourceData;

final class JournalSourceIdentityService
{
    public function identityKey(PostingSourceData $source): string
    {
        return hash('sha256', implode('|', [
            (string) $source->tenantId,
            (string) ($source->organizationUnitId ?? 'global'),
            strtolower(trim((string) $source->sourceModule)),
            strtolower(trim($source->sourceType)),
            (string) $source->sourceId,
            strtolower(trim($source->postingKey)),
        ]));
    }

    /** @throws JsonException */
    public function fingerprint(CreateJournalEntryData $data): string
    {
        $lines = $data->lines;
        usort($lines, static fn ($left, $right): int => $left->lineNumber <=> $right->lineNumber);

        $payload = [
            'tenant_id' => $data->tenantId,
            'organization_unit_id' => $data->organizationUnitId,
            'journal_date' => $data->journalDate,
            'journal_type' => $data->journalType->value,
            'posting_profile_id' => $data->postingProfileId,
            'currency_id' => $data->currencyId,
            'exchange_rate' => $data->exchangeRate,
            'description' => $data->description,
            'source' => $data->source === null ? null : [
                'module' => $data->source->sourceModule,
                'type' => $data->source->sourceType,
                'id' => $data->source->sourceId,
                'posting_key' => $data->source->postingKey,
                'number' => $data->source->sourceNumber,
                'date' => $data->source->sourceDate,
            ],
            'lines' => array_map(static fn ($line): array => [
                'account_id' => $line->accountId,
                'account_role_id' => $line->accountRoleId,
                'line_number' => $line->lineNumber,
                'debit' => $line->debit,
                'credit' => $line->credit,
                'description' => $line->description,
                'dimension_id' => $line->dimensionId,
                'source_line_type' => $line->sourceLineType,
                'source_line_id' => $line->sourceLineId,
                'account_code_snapshot' => $line->accountCodeSnapshot,
                'account_name_snapshot' => $line->accountNameSnapshot,
                'account_role_code_snapshot' => $line->accountRoleCodeSnapshot,
            ], $lines),
        ];

        return hash('sha256', json_encode($payload, JSON_THROW_ON_ERROR | JSON_PRESERVE_ZERO_FRACTION));
    }
}
