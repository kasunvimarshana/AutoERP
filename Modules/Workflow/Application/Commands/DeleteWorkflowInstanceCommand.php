<?php

declare(strict_types=1);

namespace Modules\Workflow\Application\Commands;

final readonly class DeleteWorkflowInstanceCommand
{
    public function __construct(
        public int $id,
        public int $tenantId,
    ) {}
}
