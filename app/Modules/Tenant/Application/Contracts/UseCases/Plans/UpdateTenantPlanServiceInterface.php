<?php

declare(strict_types=1);

namespace Modules\Tenant\Application\Contracts\UseCases\Plans;

use Modules\Core\Application\Results\Result;

interface UpdateTenantPlanServiceInterface
{
    /**
     * @param array<string, mixed> $payload
     */
    public function execute(int|string $id, array $payload): Result;
}
