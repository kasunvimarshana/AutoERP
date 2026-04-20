<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Contracts;

use Modules\Inventory\Domain\Entities\TransferOrder;

interface CreateTransferOrderServiceInterface
{
    public function execute(array $data): TransferOrder;
}
