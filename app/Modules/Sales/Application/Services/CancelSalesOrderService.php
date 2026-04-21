<?php

declare(strict_types=1);

namespace Modules\Sales\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Sales\Application\Contracts\CancelSalesOrderServiceInterface;
use Modules\Sales\Domain\Entities\SalesOrder;
use Modules\Sales\Domain\Exceptions\SalesOrderNotFoundException;
use Modules\Sales\Domain\RepositoryInterfaces\SalesOrderRepositoryInterface;

class CancelSalesOrderService extends BaseService implements CancelSalesOrderServiceInterface
{
    public function __construct(private readonly SalesOrderRepositoryInterface $salesOrderRepository)
    {
        parent::__construct($salesOrderRepository);
    }

    protected function handle(array $data): SalesOrder
    {
        $id = (int) ($data['id'] ?? 0);
        $order = $this->salesOrderRepository->find($id);

        if (! $order) {
            throw new SalesOrderNotFoundException($id);
        }

        $order->cancel();

        return $this->salesOrderRepository->save($order);
    }
}
