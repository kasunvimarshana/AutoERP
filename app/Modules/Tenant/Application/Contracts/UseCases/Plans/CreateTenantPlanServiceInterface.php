<?php

declare(strict_types=1);

namespace Modules\Tenant\Application\Contracts\UseCases\Plans;

use Modules\Core\Application\Results\Result;

interface CreateTenantPlanServiceInterface
{
    /**
     * @param array<string, mixed> $payload
     */
    public function execute(array $payload): Result;
}
