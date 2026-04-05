<?php

declare(strict_types=1);

namespace Modules\SalesOrder\Application\Services;

use Modules\Core\Domain\Exceptions\NotFoundException;
use Modules\SalesOrder\Application\Contracts\StartPackingSalesOrderServiceInterface;
use Modules\SalesOrder\Domain\Entities\SalesOrder;
use Modules\SalesOrder\Domain\Events\SalesOrderPackingStarted;
use Modules\SalesOrder\Domain\RepositoryInterfaces\SalesOrderRepositoryInterface;

class StartPackingSalesOrderService implements StartPackingSalesOrderServiceInterface
{
    public function __construct(private readonly SalesOrderRepositoryInterface $repo) {}

    public function execute(int $id): SalesOrder
    {
        $so = $this->repo->findById($id);
        if (!$so) throw new NotFoundException("SalesOrder", $id);
        $so->startPacking();
        $this->repo->updateStatus($id, 'packing');
        event(new SalesOrderPackingStarted($so->getTenantId(), $id));
        return $this->repo->findById($id);
    }
}
