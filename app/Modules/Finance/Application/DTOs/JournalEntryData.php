<?php

declare(strict_types=1);

namespace Modules\Finance\Application\DTOs;

class JournalEntryData
{
    /**
     * @param  array<JournalEntryLineData>  $lines
     */
    public function __construct(
        public readonly int $tenant_id,
        public readonly int $fiscal_period_id,
        public readonly string $entry_date,
        public readonly int $created_by,
        public readonly array $lines,
        public readonly string $entry_type = 'manual',
        public readonly ?string $entry_number = null,
        public readonly ?string $reference_type = null,
        public readonly ?int $reference_id = null,
        public readonly ?string $description = null,
        public readonly ?string $posting_date = null,
        public readonly string $status = 'draft',
        public readonly bool $is_reversed = false,
        public readonly ?int $reversal_entry_id = null,
        public readonly ?int $posted_by = null,
        public readonly ?string $posted_at = null,
        public readonly ?int $id = null,
    ) {}

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
            tenant_id: (int) $data['tenant_id'],
            fiscal_period_id: (int) $data['fiscal_period_id'],
            entry_date: (string) $data['entry_date'],
            created_by: (int) $data['created_by'],
            lines: $lines,
            entry_type: (string) ($data['entry_type'] ?? 'manual'),
            entry_number: isset($data['entry_number']) ? (string) $data['entry_number'] : null,
            reference_type: isset($data['reference_type']) ? (string) $data['reference_type'] : null,
            reference_id: isset($data['reference_id']) ? (int) $data['reference_id'] : null,
            description: isset($data['description']) ? (string) $data['description'] : null,
            posting_date: isset($data['posting_date']) ? (string) $data['posting_date'] : null,
            status: (string) ($data['status'] ?? 'draft'),
            is_reversed: (bool) ($data['is_reversed'] ?? false),
            reversal_entry_id: isset($data['reversal_entry_id']) ? (int) $data['reversal_entry_id'] : null,
            posted_by: isset($data['posted_by']) ? (int) $data['posted_by'] : null,
            posted_at: isset($data['posted_at']) ? (string) $data['posted_at'] : null,
            id: isset($data['id']) ? (int) $data['id'] : null,
        );
    }
}
