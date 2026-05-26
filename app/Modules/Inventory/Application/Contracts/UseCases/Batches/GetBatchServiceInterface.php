<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Contracts\UseCases\Batches;

use Modules\Core\Application\Results\Result;

interface GetBatchServiceInterface
{
    public function execute(int|string $id): Result;
}