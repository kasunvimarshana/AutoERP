<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Contracts\UseCases\TransferOrderLines;

use Modules\Core\Application\Results\Result;

interface UpdateTransferOrderLineServiceInterface
{
    /**
     * @param array<string, mixed> $payload
     */
    public function execute(int|string $id, array $payload): Result;
}