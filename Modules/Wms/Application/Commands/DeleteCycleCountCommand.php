<?php

declare(strict_types=1);

namespace Modules\Wms\Application\Commands;

final readonly class DeleteCycleCountCommand
{
    public function __construct(
        public int $id,
        public int $tenantId,
    ) {}
}
