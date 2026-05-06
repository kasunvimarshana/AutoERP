<?php

declare(strict_types=1);

namespace Modules\Finance\Application\DTOs;

class PaymentData
{
    public function __construct(
        public readonly int $tenant_id,
        public readonly string $payment_number,
        public readonly string $direction,
        public readonly string $party_type,
        public readonly int $party_id,
        public readonly int $payment_method_id,
        public readonly int $account_id,
        public readonly float $amount,
        public readonly int $currency_id,
        public readonly string $payment_date,
        public readonly float $exchange_rate = 1.0,
        public readonly float $base_amount = 0.0,
        public readonly string $status = 'draft',
        public readonly ?string $reference = null,
        public readonly ?string $notes = null,
        public readonly ?string $idempotency_key = null,
        public readonly ?int $journal_entry_id = null,
        public readonly ?int $id = null,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            tenant_id: (int) $data['tenant_id'],
            payment_number: (string) $data['payment_number'],
            direction: (string) $data['direction'],
            party_type: (string) $data['party_type'],
            party_id: (int) $data['party_id'],
            payment_method_id: (int) $data['payment_method_id'],
            account_id: (int) $data['account_id'],
            amount: (float) $data['amount'],
            currency_id: (int) $data['currency_id'],
            payment_date: (string) $data['payment_date'],
            exchange_rate: (float) ($data['exchange_rate'] ?? 1.0),
            base_amount: (float) ($data['base_amount'] ?? 0.0),
            status: (string) ($data['status'] ?? 'draft'),
            reference: isset($data['reference']) ? (string) $data['reference'] : null,
            notes: isset($data['notes']) ? (string) $data['notes'] : null,
            idempotency_key: isset($data['idempotency_key']) ? (string) $data['idempotency_key'] : null,
            journal_entry_id: isset($data['journal_entry_id']) ? (int) $data['journal_entry_id'] : null,
            id: isset($data['id']) ? (int) $data['id'] : null,
        );
    }
}
