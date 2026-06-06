<?php

declare(strict_types=1);

namespace Modules\Supplier\DTOs;

use Modules\Supplier\Enums\SupplierStatus;
use Modules\Supplier\Enums\SupplierType;

final readonly class SupplierResultData
{
    public function __construct(
        public int $supplierId,
        public int $tenantId,
        public ?int $organizationUnitId,
        public string $supplierNumber,
        public string $code,
        public string $name,
        public SupplierType $supplierType,
        public SupplierStatus $status,
        public string $creditLimit,
        public string $openingBalance,
        public bool $isCreditAllowed,
        public bool $isAdvanceAllowed,
    ) {}
}
