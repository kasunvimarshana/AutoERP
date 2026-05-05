<?php

declare(strict_types=1);

namespace Modules\Finance\Application\DTOs;

class PaymentData
{
    public function __construct(
        public readonly int $tenantId,
        public readonly string $paymentNumber,
        public readonly string $direction,
        public readonly string $partyType,
        public readonly int $partyId,
        public readonly int $paymentMethodId,
        public readonly int $accountId,
        public readonly float $amount,
        public readonly int $currencyId,
        public readonly string $paymentDate,
        public readonly float $exchangeRate = 1.0,
        public readonly float $baseAmount = 0.0,
        public readonly string $status = 'draft',
        public readonly ?string $reference = null,
        public readonly ?string $notes = null,
        public readonly ?string $idempotencyKey = null,
        public readonly ?int $journalEntryId = null,
        public readonly int $rowVersion = 1,
        public readonly ?int $id = null,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            tenantId: (int) $data['tenant_id'],
            paymentNumber: (string) $data['payment_number'],
            direction: (string) $data['direction'],
            partyType: (string) $data['party_type'],
            partyId: (int) $data['party_id'],
            paymentMethodId: (int) $data['payment_method_id'],
            accountId: (int) $data['account_id'],
            amount: (float) $data['amount'],
            currencyId: (int) $data['currency_id'],
            paymentDate: (string) $data['payment_date'],
            exchangeRate: (float) ($data['exchange_rate'] ?? 1.0),
            baseAmount: (float) ($data['base_amount'] ?? 0.0),
            status: (string) ($data['status'] ?? 'draft'),
            reference: isset($data['reference']) ? (string) $data['reference'] : null,
            notes: isset($data['notes']) ? (string) $data['notes'] : null,
            idempotencyKey: isset($data['idempotency_key']) ? (string) $data['idempotency_key'] : null,
            journalEntryId: isset($data['journal_entry_id']) ? (int) $data['journal_entry_id'] : null,
            rowVersion: isset($data['row_version']) ? (int) $data['row_version'] : 1,
            id: isset($data['id']) ? (int) $data['id'] : null,
        );
    }
}
