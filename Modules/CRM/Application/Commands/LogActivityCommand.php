<?php

declare(strict_types=1);

namespace Modules\Crm\Application\Commands;

final readonly class LogActivityCommand
{
    public function __construct(
        public int $tenantId,
        public string $type,
        public string $subject,
        public ?string $description = null,
        public ?int $contactId = null,
        public ?int $leadId = null,
        public ?string $scheduledAt = null,
        public ?string $completedAt = null,
    ) {}
}
