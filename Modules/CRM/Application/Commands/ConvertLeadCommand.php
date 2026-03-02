<?php

declare(strict_types=1);

namespace Modules\CRM\Application\Commands;

final readonly class ConvertLeadCommand
{
    public function __construct(
        public int $leadId,
        public int $tenantId,
    ) {}
}
