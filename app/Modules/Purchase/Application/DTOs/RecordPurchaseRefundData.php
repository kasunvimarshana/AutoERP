<?php

declare(strict_types=1);

namespace Modules\Purchase\Application\DTOs;

class RecordPurchaseRefundData
{
    public function __construct(
        public readonly int $tenantId,
        public readonly int $invoiceId,
        public readonly string $refundNumber,
        public readonly ?string $idempotencyKey,
        public readonly int $paymentMethodId,
        public readonly int $accountId,
        public readonly string $amount,
        public readonly int $currencyId,
        public readonly string $refundDate,
        public readonly float $exchangeRate = 1.0,
        public readonly ?string $reference = null,
        public readonly ?string $notes = null,
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            tenantId: (int) $data['tenant_id'],
            invoiceId: (int) $data['invoice_id'],
            refundNumber: (string) $data['refund_number'],
            idempotencyKey: isset($data['idempotency_key']) ? (string) $data['idempotency_key'] : null,
            paymentMethodId: (int) $data['payment_method_id'],
            accountId: (int) $data['account_id'],
            amount: (string) $data['amount'],
            currencyId: (int) $data['currency_id'],
            refundDate: (string) $data['refund_date'],
            exchangeRate: (float) ($data['exchange_rate'] ?? 1.0),
            reference: isset($data['reference']) ? (string) $data['reference'] : null,
            notes: isset($data['notes']) ? (string) $data['notes'] : null,
        );
    }
}
