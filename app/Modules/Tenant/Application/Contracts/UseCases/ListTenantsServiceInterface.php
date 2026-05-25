<?php

declare(strict_types=1);

namespace Modules\Tenant\Application\Contracts\UseCases;

use Modules\Core\Application\Results\Result;

interface ListTenantsServiceInterface
{
    /**
     * @param array<string, mixed> $filters
     */
    public function execute(array $filters): Result;
}
