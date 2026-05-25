<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Contracts\UseCases\TransferOrders;

use Modules\Core\Application\Results\Result;

interface GetTransferOrderServiceInterface
{
    public function execute(int|string $id): Result;
}