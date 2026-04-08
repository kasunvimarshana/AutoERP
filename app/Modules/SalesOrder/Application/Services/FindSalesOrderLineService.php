<?php

declare(strict_types=1);

namespace Modules\SalesOrder\Application\Services;

use Illuminate\Support\Collection;
use Modules\Core\Application\Services\BaseService;
use Modules\SalesOrder\Application\Contracts\FindSalesOrderLineServiceInterface;
use Modules\SalesOrder\Domain\RepositoryInterfaces\SalesOrderLineRepositoryInterface;

class FindSalesOrderLineService extends BaseService implements FindSalesOrderLineServiceInterface
{
    public function __construct(private readonly SalesOrderLineRepositoryInterface $lineRepository)
    {
        parent::__construct($lineRepository);
    }

    public function findBySalesOrder(int $salesOrderId): Collection
    {
        return $this->lineRepository->findBySalesOrder($salesOrderId);
    }

    protected function handle(array $data): mixed
    {
        throw new \BadMethodCallException(static::class.' does not support write operations via execute().');
    }
}
