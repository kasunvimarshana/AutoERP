<?php

declare(strict_types=1);

namespace Modules\Wms\Application\Commands;

final readonly class DeleteAisleCommand
{
    public function __construct(
        public int $id,
        public int $tenantId,
    ) {}
}
