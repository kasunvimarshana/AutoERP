<?php

declare(strict_types=1);

namespace Modules\Core\DTOs\Integration;

final readonly class SettlementResultData
{
    public function __construct(
        public int $sourceId,
        public int $tenantId,
        public ?int $organizationUnitId,
        public string $settledAmount,
        public string $balanceBefore,
        public string $balanceAfter,
        public string $status,
        public string $sourceType = 'invoice',
    ) {}
}
