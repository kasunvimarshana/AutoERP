<?php

declare(strict_types=1);

namespace Modules\CRM\Application\Commands;

final readonly class UpdateLeadCommand
{
    public function __construct(
        public int     $id,
        public int     $tenantId,
        public string  $title,
        public string  $status,
        public ?string $source            = null,
        public string  $value             = '0',
        public ?string $expectedCloseDate = null,
        public ?int    $assignedTo        = null,
        public ?string $notes             = null,
    ) {}
}
