<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Contracts\UseCases\TransferOrderLines;

use Modules\Core\Application\Results\Result;

interface GetTransferOrderLineServiceInterface
{
    public function execute(int|string $id): Result;
}