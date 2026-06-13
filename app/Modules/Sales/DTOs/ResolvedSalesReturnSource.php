<?php

declare(strict_types=1);

namespace Modules\Sales\DTOs;

final readonly class ResolvedSalesReturnSource
{
    public function __construct(
        public ?int $tenantId,
        public ?int $organizationUnitId,
        public ?int $customerId,
        public ?int $itemId,
        public ?int $itemVariantId,
        public ?int $uomId,
        public string $sourceQuantity,
        public string $previouslyReturnedQuantity,
        public ?string $unitPrice,
        public string $discountAmount,
        public string $taxAmount,
        public string $chargeAmount,
    ) {}
}
