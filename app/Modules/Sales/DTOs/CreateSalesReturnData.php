<?php

declare(strict_types=1);

namespace Modules\Sales\DTOs;

use Modules\Sales\Enums\SalesReturnType;

final readonly class CreateSalesReturnData
{
    /**
     * @param  list<SalesReturnLineData>  $lines
     */
    public function __construct(
        public int $tenantId,
        public string $returnDate,
        public int $customerId,
        public SalesReturnType $returnType,
        public ?int $organizationUnitId = null,
        public ?string $returnNumber = null,
        public ?int $warehouseId = null,
        public ?int $warehouseLocationId = null,
        public ?string $reason = null,
        public ?int $replacementSalesOrderId = null,
        public bool $approvalRequired = false,
        public ?string $costBasis = null,
        public ?array $auditMetadata = null,
        public ?int $createdBy = null,
        public array $lines = [],
    ) {}
}
