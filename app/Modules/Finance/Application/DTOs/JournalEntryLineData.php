<?php

declare(strict_types=1);

namespace Modules\Finance\Application\DTOs;

class JournalEntryLineData
{
    /**
     * @param  array<string, mixed>|null  $metadata
     */
    public function __construct(
        public readonly int $accountId,
        public readonly float $debitAmount = 0.0,
        public readonly float $creditAmount = 0.0,
        public readonly ?string $description = null,
        public readonly ?int $currencyId = null,
        public readonly float $exchangeRate = 1.0,
        public readonly float $baseDebitAmount = 0.0,
        public readonly float $baseCreditAmount = 0.0,
        public readonly ?int $costCenterId = null,
        public readonly ?array $metadata = null,
    )
    {
    }

    /**
     * @param  array<string, mixed>  $line
     */
    public static function fromArray(array $line): self
    {
        return new self(
            accountId: (int) $line['account_id'],
            debitAmount: (float) ($line['debit_amount'] ?? 0.0),
            creditAmount: (float) ($line['credit_amount'] ?? 0.0),
            description: isset($line['description']) ? (string) $line['description'] : null,
            currencyId: isset($line['currency_id']) ? (int) $line['currency_id'] : null,
            exchangeRate: (float) ($line['exchange_rate'] ?? 1.0),
            baseDebitAmount: (float) ($line['base_debit_amount'] ?? 0.0),
            baseCreditAmount: (float) ($line['base_credit_amount'] ?? 0.0),
            costCenterId: isset($line['cost_center_id']) ? (int) $line['cost_center_id'] : null,
            metadata: isset($line['metadata']) && is_array($line['metadata']) ? $line['metadata'] : null,
        );
    }
}
