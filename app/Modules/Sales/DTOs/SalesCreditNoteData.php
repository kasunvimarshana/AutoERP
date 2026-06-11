<?php

declare(strict_types=1);

namespace Modules\Sales\DTOs;

final readonly class SalesCreditNoteData
{
    public function __construct(
        public int $tenantId,
        public string $creditNoteDate,
        public int $customerId,
        public string $amount,
        public ?int $organizationUnitId = null,
        public ?string $creditNoteNumber = null,
        public ?int $salesReturnId = null,
        public ?string $reason = null,
    ) {}
}
