<?php

declare(strict_types=1);

namespace Modules\HR\Application\Contracts\UseCases\LeavePolicies;

use Modules\Core\Application\Results\Result;

interface DeleteLeavePolicyServiceInterface
{
    public function execute(int|string $id): Result;
}