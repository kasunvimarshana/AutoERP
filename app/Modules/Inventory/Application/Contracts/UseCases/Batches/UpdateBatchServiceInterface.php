<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Contracts\UseCases\Batches;

use Modules\Core\Application\Results\Result;

interface UpdateBatchServiceInterface
{
    /**
     * @param array<string, mixed> $payload
     */
    public function execute(int|string $id, array $payload): Result;
}