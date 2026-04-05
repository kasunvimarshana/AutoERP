<?php

declare(strict_types=1);

namespace Modules\SalesOrder\Application\Services;

use Modules\Core\Domain\Exceptions\NotFoundException;
use Modules\SalesOrder\Application\Contracts\ConfirmSalesOrderServiceInterface;
use Modules\SalesOrder\Domain\Entities\SalesOrder;
use Modules\SalesOrder\Domain\Events\SalesOrderConfirmed;
use Modules\SalesOrder\Domain\RepositoryInterfaces\SalesOrderRepositoryInterface;

class ConfirmSalesOrderService implements ConfirmSalesOrderServiceInterface
{
    public function __construct(private readonly SalesOrderRepositoryInterface $repo) {}

    public function execute(int $id): SalesOrder
    {
        $so = $this->repo->findById($id);
        if (!$so) throw new NotFoundException("SalesOrder", $id);
        $so->confirm();
        $this->repo->updateStatus($id, 'confirmed');
        event(new SalesOrderConfirmed($so->getTenantId(), $id));
        return $this->repo->findById($id);
    }
}
