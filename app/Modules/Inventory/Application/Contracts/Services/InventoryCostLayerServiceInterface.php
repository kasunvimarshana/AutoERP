<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Contracts\Services;

use Modules\Core\Application\Results\Result;

interface InventoryCostLayerServiceInterface
{
    /**
     * @param array<string, mixed> $payload
     * @return Result<\Modules\Core\Application\DTO\DataRecord>
     */
    public function createLayer(array $payload): Result;

    /**
     * @param array<string, mixed> $payload
     * @return Result<\Modules\Core\Application\DTO\DataRecord>
     */
    public function updateLayer(int|string $id, array $payload): Result;
}
