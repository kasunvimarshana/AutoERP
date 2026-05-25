<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Contracts\UseCases\Batches;

use Modules\Core\Application\Results\Result;

interface CreateBatcheServiceInterface
{
    /**
     * @param array<string, mixed> $payload
     */
    public function execute(array $payload): Result;
}