<?php

declare(strict_types=1);

namespace Modules\Crm\Application\Commands;

final readonly class CreateLeadCommand
{
    public function __construct(
        public int $tenantId,
        public string $title,
        public ?string $description = null,
        public ?int $contactId = null,
        public ?string $status = null,
        public ?string $estimatedValue = null,
        public ?string $currency = null,
        public ?string $expectedCloseDate = null,
        public ?string $notes = null,
    ) {}
}
