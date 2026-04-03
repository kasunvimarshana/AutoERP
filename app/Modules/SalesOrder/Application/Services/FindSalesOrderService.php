<?php

declare(strict_types=1);

namespace Modules\SalesOrder\Application\Services;

use Illuminate\Support\Collection;
use Modules\Core\Application\Services\BaseService;
use Modules\SalesOrder\Application\Contracts\FindSalesOrderServiceInterface;
use Modules\SalesOrder\Domain\RepositoryInterfaces\SalesOrderRepositoryInterface;

class FindSalesOrderService extends BaseService implements FindSalesOrderServiceInterface
{
    public function __construct(private readonly SalesOrderRepositoryInterface $orderRepository)
    {
        parent::__construct($orderRepository);
    }

    public function findByCustomer(int $tenantId, int $customerId): Collection
    {
        return $this->orderRepository->findByCustomer($tenantId, $customerId);
    }

    public function findByStatus(int $tenantId, string $status): Collection
    {
        return $this->orderRepository->findByStatus($tenantId, $status);
    }

    protected function handle(array $data): mixed
    {
        throw new \BadMethodCallException(static::class.' does not support write operations via execute().');
    }
}
