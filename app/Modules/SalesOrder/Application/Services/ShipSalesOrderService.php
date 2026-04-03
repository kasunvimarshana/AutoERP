<?php

declare(strict_types=1);

namespace Modules\SalesOrder\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\SalesOrder\Application\Contracts\ShipSalesOrderServiceInterface;
use Modules\SalesOrder\Domain\Entities\SalesOrder;
use Modules\SalesOrder\Domain\Events\SalesOrderShipped;
use Modules\SalesOrder\Domain\Exceptions\SalesOrderNotFoundException;
use Modules\SalesOrder\Domain\RepositoryInterfaces\SalesOrderRepositoryInterface;

class ShipSalesOrderService extends BaseService implements ShipSalesOrderServiceInterface
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

        $order->ship((int) $data['shipped_by']);

        $saved = $this->orderRepository->save($order);
        $this->addEvent(new SalesOrderShipped($saved->getId(), (int) $data['shipped_by']));

        return $saved;
    }
}
