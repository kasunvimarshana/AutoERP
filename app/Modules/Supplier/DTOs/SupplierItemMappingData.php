<?php

declare(strict_types=1);

namespace Modules\Supplier\DTOs;

final readonly class SupplierItemMappingData
{
    public function __construct(
        public int $itemId,
        public ?int $itemVariantId = null,
        public ?string $supplierItemCode = null,
        public ?string $supplierItemName = null,
        public ?int $defaultPurchaseUomId = null,
        public string $minimumOrderQuantity = '0.000000',
        public ?int $leadTimeDays = null,
        public bool $isPreferred = false,
        public bool $isActive = true,
    ) {}
}
