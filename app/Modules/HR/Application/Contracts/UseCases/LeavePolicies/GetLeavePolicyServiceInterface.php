<?php

declare(strict_types=1);

namespace Modules\HR\Application\Contracts\UseCases\LeavePolicies;

use Modules\Core\Application\Results\Result;

interface GetLeavePolicyServiceInterface
{
    public function execute(int|string $id): Result;
}