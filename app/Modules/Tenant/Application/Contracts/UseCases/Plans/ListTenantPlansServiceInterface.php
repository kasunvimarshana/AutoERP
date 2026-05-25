<?php

declare(strict_types=1);

namespace Modules\Tenant\Application\Contracts\UseCases\Plans;

use Modules\Core\Application\Results\Result;

interface ListTenantPlansServiceInterface
{
    /**
     * @param array<string, mixed> $filters
     */
    public function execute(array $filters): Result;
}
