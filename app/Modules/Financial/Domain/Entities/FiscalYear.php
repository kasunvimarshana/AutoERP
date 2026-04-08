<?php

declare(strict_types=1);

namespace Modules\Financial\Domain\Entities;

/**
 * Fiscal year domain entity.
 */
class FiscalYear
{
    public function __construct(
        public readonly string $id,
        public readonly int $tenantId,
        public readonly string $name,
        public readonly string $startDate,
        public readonly string $endDate,
        public readonly string $status,
    ) {}
}
