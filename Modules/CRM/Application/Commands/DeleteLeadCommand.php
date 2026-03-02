<?php

declare(strict_types=1);

namespace Modules\Crm\Application\Commands;

final readonly class DeleteLeadCommand
{
    public function __construct(
        public int $id,
        public int $tenantId,
    ) {}
}
