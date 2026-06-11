<?php

declare(strict_types=1);

namespace Modules\Inventory\DTOs;

final readonly class CostAdjustmentData
{
    /**
     * @param  list<CostAdjustmentLineData>  $lines
     */
    public function __construct(
        public int $tenantId,
        public string $adjustmentDate,
        public ?int $organizationUnitId = null,
        public ?string $adjustmentNumber = null,
        public ?string $reason = null,
        public ?string $notes = null,
        public ?int $createdBy = null,
        public array $lines = [],
    ) {}
}
