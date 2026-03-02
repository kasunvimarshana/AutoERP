<?php

declare(strict_types=1);

namespace Modules\Crm\Domain\Entities;

use Modules\Crm\Domain\Enums\LeadStatus;

final class Lead
{
    public function __construct(
        public readonly ?int $id,
        public readonly int $tenantId,
        public readonly ?int $contactId,
        public readonly string $title,
        public readonly ?string $description,
        public readonly LeadStatus $status,
        public readonly string $estimatedValue,
        public readonly string $currency,
        public readonly ?string $expectedCloseDate,
        public readonly ?string $notes,
        public readonly ?string $createdAt,
        public readonly ?string $updatedAt,
    ) {}
}
