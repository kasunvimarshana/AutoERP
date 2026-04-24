<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Contracts;

use Modules\Inventory\Domain\Entities\Batch;

interface UpdateBatchServiceInterface
{
    public function execute(array $data): Batch;
}
