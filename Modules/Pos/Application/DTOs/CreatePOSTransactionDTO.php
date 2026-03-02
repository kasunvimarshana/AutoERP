<?php

declare(strict_types=1);

namespace Modules\POS\Application\DTOs;

/**
 * Data Transfer Object for creating a POS transaction.
 *
 * Lines array format:
 *   [['product_id' => int, 'uom_id' => int, 'quantity' => string,
 *     'unit_price' => string, 'discount_amount' => string], ...]
 *
 * Payments array format:
 *   [['payment_method' => string, 'amount' => string, 'reference' => ?string], ...]
 *
 * All monetary/quantity values MUST be passed as numeric strings for BCMath precision.
 */
final class CreatePOSTransactionDTO
{
    /**
     * @param array<int, array{product_id: int, uom_id: int, quantity: string, unit_price: string, discount_amount: string}> $lines
     * @param array<int, array{payment_method: string, amount: string, reference?: string|null}> $payments
     */
    public function __construct(
        public readonly int $sessionId,
        public readonly array $lines,
        public readonly array $payments,
        public readonly string $discountAmount,
        public readonly bool $isOffline,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            sessionId: (int) $data['session_id'],
            lines: $data['lines'],
            payments: $data['payments'],
            discountAmount: isset($data['discount_amount']) ? (string) $data['discount_amount'] : '0',
            isOffline: (bool) ($data['is_offline'] ?? false),
        );
    }
}
