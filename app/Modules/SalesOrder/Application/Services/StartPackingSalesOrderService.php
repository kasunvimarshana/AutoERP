<?php

declare(strict_types=1);

namespace Modules\SalesOrder\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\SalesOrder\Application\Contracts\StartPackingSalesOrderServiceInterface;
use Modules\SalesOrder\Domain\Entities\SalesOrder;
use Modules\SalesOrder\Domain\Events\SalesOrderPackingStarted;
use Modules\SalesOrder\Domain\Exceptions\SalesOrderNotFoundException;
use Modules\SalesOrder\Domain\RepositoryInterfaces\SalesOrderRepositoryInterface;

class StartPackingSalesOrderService extends BaseService implements StartPackingSalesOrderServiceInterface
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

        $order->startPacking();

        $saved = $this->orderRepository->save($order);
        $this->addEvent(new SalesOrderPackingStarted($saved->getId(), $saved->getTenantId()));

        return $saved;
    }
}
