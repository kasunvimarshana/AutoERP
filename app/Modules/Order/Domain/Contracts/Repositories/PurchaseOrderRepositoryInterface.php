<?php

declare(strict_types=1);

namespace Modules\Order\Domain\Contracts\Repositories;

use Modules\Core\Domain\Contracts\Repositories\RepositoryInterface;

interface PurchaseOrderRepositoryInterface extends RepositoryInterface
{
    public function findByOrderNumber(string $number): mixed;
}
