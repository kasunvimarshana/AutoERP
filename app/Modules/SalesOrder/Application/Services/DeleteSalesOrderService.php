<?php

declare(strict_types=1);

namespace Modules\SalesOrder\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\SalesOrder\Application\Contracts\DeleteSalesOrderServiceInterface;
use Modules\SalesOrder\Domain\Events\SalesOrderDeleted;
use Modules\SalesOrder\Domain\Exceptions\SalesOrderNotFoundException;
use Modules\SalesOrder\Domain\RepositoryInterfaces\SalesOrderRepositoryInterface;

class DeleteSalesOrderService extends BaseService implements DeleteSalesOrderServiceInterface
{
    public function __construct(private readonly SalesOrderRepositoryInterface $orderRepository)
    {
        parent::__construct($orderRepository);
    }

    protected function handle(array $data): bool
    {
        $id    = $data['id'];
        $order = $this->orderRepository->find($id);

        if (! $order) {
            throw new SalesOrderNotFoundException($id);
        }

        $this->addEvent(new SalesOrderDeleted($id, $order->getTenantId()));

        return $this->orderRepository->delete($id);
    }
}
