<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Invoice\DTOs;

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
