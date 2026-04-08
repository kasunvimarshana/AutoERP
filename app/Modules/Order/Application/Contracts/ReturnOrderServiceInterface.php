<?php

declare(strict_types=1);

namespace Modules\Order\Application\Contracts;

use Modules\Core\Application\Contracts\ServiceInterface;

interface ReturnOrderServiceInterface extends ServiceInterface
{
    public function createReturnOrder(array $data): mixed;
    public function confirmReturn(string $id): mixed;
}
