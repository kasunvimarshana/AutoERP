<?php

declare(strict_types=1);

namespace Modules\HR\Application\Contracts\UseCases\LeavePolicyLines;

use Modules\Core\Application\Results\Result;

interface DeleteLeavePolicyLineServiceInterface
{
    public function execute(int|string $id): Result;
}