<?php

declare(strict_types=1);

namespace Modules\SalesOrder\Application\Services;

use Modules\Core\Domain\Exceptions\NotFoundException;
use Modules\SalesOrder\Application\Contracts\StartPickingSalesOrderServiceInterface;
use Modules\SalesOrder\Domain\Entities\SalesOrder;
use Modules\SalesOrder\Domain\Events\SalesOrderPickingStarted;
use Modules\SalesOrder\Domain\RepositoryInterfaces\SalesOrderRepositoryInterface;

class StartPickingSalesOrderService implements StartPickingSalesOrderServiceInterface
{
    public function __construct(private readonly SalesOrderRepositoryInterface $repo) {}

    public function execute(int $id): SalesOrder
    {
        $so = $this->repo->findById($id);
        if (!$so) throw new NotFoundException("SalesOrder", $id);
        $so->startPicking();
        $this->repo->updateStatus($id, 'picking');
        event(new SalesOrderPickingStarted($so->getTenantId(), $id));
        return $this->repo->findById($id);
    }
}
