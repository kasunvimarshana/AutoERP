<?php

declare(strict_types=1);

namespace Modules\Ecommerce\Domain\Entities;

final class StorefrontCart
{
    public function __construct(
        public readonly ?int $id,
        public readonly int $tenantId,
        public readonly ?int $userId,
        public readonly string $token,
        public readonly string $status,
        public readonly string $currency,
        public readonly string $subtotal,
        public readonly string $taxAmount,
        public readonly string $totalAmount,
        public readonly ?string $createdAt,
        public readonly ?string $updatedAt,
    ) {}
}
