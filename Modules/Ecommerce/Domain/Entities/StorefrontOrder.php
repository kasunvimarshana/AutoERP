<?php

declare(strict_types=1);

namespace Modules\Ecommerce\Domain\Entities;

final class StorefrontOrder
{
    public function __construct(
        public readonly ?int $id,
        public readonly int $tenantId,
        public readonly ?int $userId,
        public readonly string $reference,
        public readonly string $status,
        public readonly string $currency,
        public readonly string $subtotal,
        public readonly string $taxAmount,
        public readonly string $shippingAmount,
        public readonly string $discountAmount,
        public readonly string $totalAmount,
        public readonly ?string $billingName,
        public readonly ?string $billingEmail,
        public readonly ?string $billingPhone,
        public readonly ?string $shippingAddress,
        public readonly ?string $notes,
        public readonly ?string $cartToken,
        public readonly ?string $createdAt,
        public readonly ?string $updatedAt,
    ) {}
}
