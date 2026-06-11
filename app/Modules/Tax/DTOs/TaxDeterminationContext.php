<?php

declare(strict_types=1);

namespace Modules\Tax\DTOs;

final readonly class TaxDeterminationContext
{
    public function __construct(
        public int $tenantId,
        public string $documentType,
        public string $documentDate,
        public ?int $organizationUnitId = null,
        public ?int $customerId = null,
        public ?int $supplierId = null,
        public ?int $itemId = null,
        public ?int $documentTaxGroupId = null,
    ) {}
}
