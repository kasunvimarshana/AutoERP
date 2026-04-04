<?php

namespace Modules\SalesOrder\Application\Services;

use Illuminate\Support\Facades\Event;
use Modules\SalesOrder\Application\Contracts\CancelSalesOrderServiceInterface;
use Modules\SalesOrder\Domain\Entities\SalesOrder;
use Modules\SalesOrder\Domain\Events\SalesOrderCancelled;
use Modules\SalesOrder\Domain\RepositoryInterfaces\SalesOrderRepositoryInterface;
use Modules\SalesOrder\Domain\ValueObjects\SalesOrderStatus;

class CancelSalesOrderService implements CancelSalesOrderServiceInterface
{
    public function __construct(private readonly SalesOrderRepositoryInterface $repository) {}

    public function execute(SalesOrder $so): SalesOrder
    {
        if (in_array($so->status, [SalesOrderStatus::DELIVERED, SalesOrderStatus::CLOSED], true)) {
            throw new \DomainException("Cannot cancel a sales order with status: {$so->status}");
        }
        $so = $this->repository->update($so, ['status' => SalesOrderStatus::CANCELLED]);
        Event::dispatch(new SalesOrderCancelled($so->tenantId, $so->id));
        return $so;
    }
}
