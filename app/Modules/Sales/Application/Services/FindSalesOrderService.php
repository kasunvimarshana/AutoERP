<?php

declare(strict_types=1);

namespace Modules\Sales\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Sales\Application\Contracts\FindSalesOrderServiceInterface;
use Modules\Sales\Domain\RepositoryInterfaces\SalesOrderRepositoryInterface;

class FindSalesOrderService extends BaseService implements FindSalesOrderServiceInterface
{
    public function __construct(private readonly SalesOrderRepositoryInterface $salesOrderRepository)
    {
        parent::__construct($salesOrderRepository);
    }

    protected function handle(array $data): mixed
    {
        return null;
    }
}
