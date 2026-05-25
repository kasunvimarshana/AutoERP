<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Contracts\UseCases\Batches;

use Modules\Core\Application\Results\Result;

interface GetBatcheServiceInterface
{
    public function execute(int|string $id): Result;
}