<?php

declare(strict_types=1);

namespace Modules\Accounting\Application\DTOs;

/**
 * Data Transfer Object for creating a JournalEntry.
 *
 * Lines array format:
 *   [
 *     ['account_id' => int, 'type' => 'debit'|'credit', 'amount' => string, 'description' => ?string],
 *     ...
 *   ]
 *
 * Amount values MUST be passed as numeric strings to preserve BCMath precision.
 */
final class CreateJournalEntryDTO
{
    /**
     * @param array<int, array{account_id: int, type: string, amount: string, description?: string|null}> $lines
     */
    public function __construct(
        public readonly int $fiscalPeriodId,
        public readonly string $referenceNumber,
        public readonly ?string $description,
        public readonly string $entryDate,
        public readonly array $lines,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            fiscalPeriodId: (int) $data['fiscal_period_id'],
            referenceNumber: (string) $data['reference_number'],
            description: $data['description'] ?? null,
            entryDate: (string) $data['entry_date'],
            lines: $data['lines'],
        );
    }
}
