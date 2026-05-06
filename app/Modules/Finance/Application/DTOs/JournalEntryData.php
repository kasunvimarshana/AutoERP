<?php

declare(strict_types=1);

namespace Modules\Finance\Application\DTOs;

class JournalEntryData
{
    /**
     * @param  array<JournalEntryLineData>  $lines
     */
    public function __construct(
        public readonly int $tenantId,
        public readonly int $fiscalPeriodId,
        public readonly string $entryDate,
        public readonly int $createdBy,
        public readonly array $lines,
        public readonly string $entryType = 'manual',
        public readonly ?string $entryNumber = null,
        public readonly ?string $referenceType = null,
        public readonly ?int $referenceId = null,
        public readonly ?string $description = null,
        public readonly ?string $postingDate = null,
        public readonly string $status = 'draft',
        public readonly bool $isReversed = false,
        public readonly ?int $reversalEntryId = null,
        public readonly ?int $postedBy = null,
        public readonly ?string $postedAt = null,
        public readonly int $rowVersion = 1,
        public readonly ?int $id = null,
    )
    {
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $lines = [];
        foreach ((array) ($data['lines'] ?? []) as $line) {
            if (! is_array($line)) {
                continue;
            }

            $lines[] = JournalEntryLineData::fromArray($line);
        }

        return new self(
            tenantId: (int) $data['tenant_id'],
            fiscalPeriodId: (int) $data['fiscal_period_id'],
            entryDate: (string) $data['entry_date'],
            createdBy: (int) $data['created_by'],
            lines: $lines,
            entryType: (string) ($data['entry_type'] ?? 'manual'),
            entryNumber: isset($data['entry_number']) ? (string) $data['entry_number'] : null,
            referenceType: isset($data['reference_type']) ? (string) $data['reference_type'] : null,
            referenceId: isset($data['reference_id']) ? (int) $data['reference_id'] : null,
            description: isset($data['description']) ? (string) $data['description'] : null,
            postingDate: isset($data['posting_date']) ? (string) $data['posting_date'] : null,
            status: (string) ($data['status'] ?? 'draft'),
            isReversed: (bool) ($data['is_reversed'] ?? false),
            reversalEntryId: isset($data['reversal_entry_id']) ? (int) $data['reversal_entry_id'] : null,
            postedBy: isset($data['posted_by']) ? (int) $data['posted_by'] : null,
            postedAt: isset($data['posted_at']) ? (string) $data['posted_at'] : null,
            rowVersion: isset($data['row_version']) ? (int) $data['row_version'] : 1,
            id: isset($data['id']) ? (int) $data['id'] : null,
        );
    }
}
