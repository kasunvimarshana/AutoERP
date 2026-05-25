<?php

declare(strict_types=1);

namespace Modules\Tenant\Application\Contracts\UseCases;

use Modules\Core\Application\Results\Result;

interface ActivateTenantServiceInterface
{
    public function execute(int|string $id): Result;
}
