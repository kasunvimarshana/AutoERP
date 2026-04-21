<?php

declare(strict_types=1);

namespace Modules\Sales\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Sales\Application\Contracts\DeleteSalesOrderServiceInterface;
use Modules\Sales\Domain\Exceptions\SalesOrderNotFoundException;
use Modules\Sales\Domain\RepositoryInterfaces\SalesOrderRepositoryInterface;

class DeleteSalesOrderService extends BaseService implements DeleteSalesOrderServiceInterface
{
    public function __construct(private readonly SalesOrderRepositoryInterface $salesOrderRepository)
    {
        parent::__construct($salesOrderRepository);
    }

    protected function handle(array $data): bool
    {
        $id = (int) ($data['id'] ?? 0);
        $order = $this->salesOrderRepository->find($id);

        if (! $order) {
            throw new SalesOrderNotFoundException($id);
        }

        if ($order->getStatus() !== 'draft') {
            throw new \InvalidArgumentException('Only draft sales orders can be deleted.');
        }

        return $this->salesOrderRepository->delete($id);
    }
}
