<?php

declare(strict_types=1);

namespace Modules\Purchase\Application\DTOs;

class RecordPurchasePaymentData
{
    public function __construct(
        public readonly int $tenant_id,
        public readonly int $invoice_id,
        public readonly string $payment_number,
        public readonly ?string $idempotency_key,
        public readonly int $payment_method_id,
        public readonly int $account_id,
        public readonly string $amount,
        public readonly int $currency_id,
        public readonly string $payment_date,
        public readonly float $exchange_rate = 1.0,
        public readonly ?string $reference = null,
        public readonly ?string $notes = null,
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            tenant_id: (int) $data['tenant_id'],
            invoice_id: (int) $data['invoice_id'],
            payment_number: (string) $data['payment_number'],
            idempotency_key: isset($data['idempotency_key']) ? (string) $data['idempotency_key'] : null,
            payment_method_id: (int) $data['payment_method_id'],
            account_id: (int) $data['account_id'],
            amount: (string) $data['amount'],
            currency_id: (int) $data['currency_id'],
            payment_date: (string) $data['payment_date'],
            exchange_rate: (float) ($data['exchange_rate'] ?? 1.0),
            reference: isset($data['reference']) ? (string) $data['reference'] : null,
            notes: isset($data['notes']) ? (string) $data['notes'] : null,
        );
    }
}
