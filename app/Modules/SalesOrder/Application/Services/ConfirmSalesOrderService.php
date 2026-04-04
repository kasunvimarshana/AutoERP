<?php

namespace Modules\SalesOrder\Application\Services;

use Illuminate\Support\Facades\Event;
use Modules\SalesOrder\Application\Contracts\ConfirmSalesOrderServiceInterface;
use Modules\SalesOrder\Domain\Entities\SalesOrder;
use Modules\SalesOrder\Domain\Events\SalesOrderConfirmed;
use Modules\SalesOrder\Domain\RepositoryInterfaces\SalesOrderRepositoryInterface;
use Modules\SalesOrder\Domain\ValueObjects\SalesOrderStatus;

class ConfirmSalesOrderService implements ConfirmSalesOrderServiceInterface
{
    public function __construct(private readonly SalesOrderRepositoryInterface $repository) {}

    public function execute(SalesOrder $so): SalesOrder
    {
        if ($so->status === SalesOrderStatus::CANCELLED) {
            throw new \DomainException("Cannot confirm a cancelled sales order.");
        }
        $so = $this->repository->update($so, ['status' => SalesOrderStatus::CONFIRMED]);
        Event::dispatch(new SalesOrderConfirmed($so->tenantId, $so->id));
        return $so;
    }
}
