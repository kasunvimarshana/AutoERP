<?php

declare(strict_types=1);

namespace Modules\Finance\Application\DTOs;

class JournalEntryLineData
{
    /**
     * @param  array<string, mixed>|null  $metadata
     */
    public function __construct(
        public readonly int $account_id,
        public readonly float $debit_amount = 0.0,
        public readonly float $credit_amount = 0.0,
        public readonly ?string $description = null,
        public readonly ?int $currency_id = null,
        public readonly float $exchange_rate = 1.0,
        public readonly float $base_debit_amount = 0.0,
        public readonly float $base_credit_amount = 0.0,
        public readonly ?int $cost_center_id = null,
        public readonly ?array $metadata = null,
    ) {}

    /**
     * @param  array<string, mixed>  $line
     */
    public static function fromArray(array $line): self
    {
        return new self(
            account_id: (int) $line['account_id'],
            debit_amount: (float) ($line['debit_amount'] ?? 0.0),
            credit_amount: (float) ($line['credit_amount'] ?? 0.0),
            description: isset($line['description']) ? (string) $line['description'] : null,
            currency_id: isset($line['currency_id']) ? (int) $line['currency_id'] : null,
            exchange_rate: (float) ($line['exchange_rate'] ?? 1.0),
            base_debit_amount: (float) ($line['base_debit_amount'] ?? 0.0),
            base_credit_amount: (float) ($line['base_credit_amount'] ?? 0.0),
            cost_center_id: isset($line['cost_center_id']) ? (int) $line['cost_center_id'] : null,
            metadata: isset($line['metadata']) && is_array($line['metadata']) ? $line['metadata'] : null,
        );
    }
}
