<?php

declare(strict_types=1);

namespace Modules\Tenant\Application\Contracts\UseCases\Plans;

use Modules\Core\Application\Results\Result;

interface DeleteTenantPlanServiceInterface
{
    public function execute(int|string $id): Result;
}
