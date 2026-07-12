<?php

declare(strict_types=1);

namespace Modules\Finance\DTOs;

final readonly class AccountingPeriodData
{
    public function __construct(
        public int $tenantId,
        public ?int $organizationUnitId,
        public string $code,
        public string $name,
        public string $startDate,
        public string $endDate,
        public ?int $createdBy = null,
    ) {}
}
