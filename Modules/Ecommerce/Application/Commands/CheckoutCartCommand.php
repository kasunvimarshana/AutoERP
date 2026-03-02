<?php

declare(strict_types=1);

namespace Modules\Ecommerce\Application\Commands;

readonly class CheckoutCartCommand
{
    public function __construct(
        public int $tenantId,
        public string $cartToken,
        public ?int $userId,
        public string $billingName,
        public string $billingEmail,
        public ?string $billingPhone,
        public ?string $shippingAddress,
        public string $shippingAmount,
        public string $discountAmount,
        public ?string $notes,
    ) {}
}
