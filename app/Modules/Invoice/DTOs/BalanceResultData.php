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

final readonly class BalanceResultData
{
    public function __construct(
        public int $sourceId,
        public int $tenantId,
        public ?int $organizationUnitId,
        public string $totalAmount,
        public string $paidAmount,
        public string $creditAmount,
        public string $remainingAmount,
        public string $status,
        public ?string $partyType,
        public ?int $partyId,
        public ?int $currencyId,
        public string $sourceType = 'invoice',
    ) {}
}
