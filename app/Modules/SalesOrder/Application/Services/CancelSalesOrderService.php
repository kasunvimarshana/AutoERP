<?php

declare(strict_types=1);

namespace Modules\SalesOrder\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\SalesOrder\Application\Contracts\CancelSalesOrderServiceInterface;
use Modules\SalesOrder\Domain\Entities\SalesOrder;
use Modules\SalesOrder\Domain\Events\SalesOrderCancelled;
use Modules\SalesOrder\Domain\Exceptions\SalesOrderNotFoundException;
use Modules\SalesOrder\Domain\RepositoryInterfaces\SalesOrderRepositoryInterface;

class CancelSalesOrderService extends BaseService implements CancelSalesOrderServiceInterface
{
    public function __construct(private readonly SalesOrderRepositoryInterface $orderRepository)
    {
        parent::__construct($orderRepository);
    }

    protected function handle(array $data): SalesOrder
    {
        $id    = $data['id'];
        $order = $this->orderRepository->find($id);

        if (! $order) {
            throw new SalesOrderNotFoundException($id);
        }

        $order->cancel();

        $saved = $this->orderRepository->save($order);
        $this->addEvent(new SalesOrderCancelled($saved->getId(), $saved->getTenantId()));

        return $saved;
    }
}
