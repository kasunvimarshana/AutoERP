<?php

declare(strict_types=1);

namespace Modules\Product\Application\Contracts;

interface RebuildProductSearchProjectionServiceInterface
{
    public function execute(int $tenantId): int;
}
