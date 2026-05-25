<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Contracts\UseCases\TransferOrderLines;

use Modules\Core\Application\Results\Result;

interface CreateTransferOrderLineServiceInterface
{
    /**
     * @param array<string, mixed> $payload
     */
    public function execute(array $payload): Result;
}