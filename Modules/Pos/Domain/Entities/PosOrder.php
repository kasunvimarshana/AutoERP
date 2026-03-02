<?php

declare(strict_types=1);

namespace Modules\Pos\Domain\Entities;

final class PosOrder
{
    public function __construct(
        public readonly ?int $id,
        public readonly int $tenantId,
        public readonly int $posSessionId,
        public readonly string $reference,
        public readonly string $status,
        public readonly string $currency,
        public readonly string $subtotal,
        public readonly string $taxAmount,
        public readonly string $discountAmount,
        public readonly string $totalAmount,
        public readonly string $paidAmount,
        public readonly string $changeAmount,
        public readonly ?string $notes,
        public readonly ?string $createdAt,
        public readonly ?string $updatedAt,
    ) {}
}
