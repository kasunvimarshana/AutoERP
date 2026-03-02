<?php

declare(strict_types=1);

namespace Modules\Sales\Application\Services;

use Modules\Sales\Application\Commands\CancelSalesOrderCommand;
use Modules\Sales\Application\Commands\ConfirmSalesOrderCommand;
use Modules\Sales\Application\Commands\CreateSalesOrderCommand;
use Modules\Sales\Application\Commands\DeleteSalesOrderCommand;
use Modules\Sales\Application\Handlers\CancelSalesOrderHandler;
use Modules\Sales\Application\Handlers\ConfirmSalesOrderHandler;
use Modules\Sales\Application\Handlers\CreateSalesOrderHandler;
use Modules\Sales\Application\Handlers\DeleteSalesOrderHandler;
use Modules\Sales\Domain\Contracts\SalesOrderRepositoryInterface;
use Modules\Sales\Domain\Entities\SalesOrder;

/**
 * Service orchestrating all sales-order operations.
 *
 * Controllers must interact with the sales-order domain exclusively through
 * this service. Read operations are fulfilled directly via the repository
 * contract; write operations are delegated to the appropriate command handlers.
 */
class SalesOrderService
{
    public function __construct(
        private readonly SalesOrderRepositoryInterface $salesOrderRepository,
        private readonly CreateSalesOrderHandler $createSalesOrderHandler,
        private readonly ConfirmSalesOrderHandler $confirmSalesOrderHandler,
        private readonly CancelSalesOrderHandler $cancelSalesOrderHandler,
        private readonly DeleteSalesOrderHandler $deleteSalesOrderHandler,
    ) {}

    /**
     * Retrieve a paginated list of sales orders for the given tenant.
     *
     * @return array{items: SalesOrder[], current_page: int, last_page: int, per_page: int, total: int}
     */
    public function listSalesOrders(int $tenantId, int $page, int $perPage): array
    {
        return $this->salesOrderRepository->findAll($tenantId, $page, $perPage);
    }

    /**
     * Find a single sales order by its identifier within the given tenant.
     */
    public function findSalesOrderById(int $orderId, int $tenantId): ?SalesOrder
    {
        return $this->salesOrderRepository->findById($orderId, $tenantId);
    }

    /**
     * Create a new sales order and return the persisted entity.
     */
    public function createSalesOrder(CreateSalesOrderCommand $command): SalesOrder
    {
        return $this->createSalesOrderHandler->handle($command);
    }

    /**
     * Confirm a draft sales order and return the updated entity.
     */
    public function confirmSalesOrder(ConfirmSalesOrderCommand $command): SalesOrder
    {
        return $this->confirmSalesOrderHandler->handle($command);
    }

    /**
     * Cancel a sales order and return the updated entity.
     */
    public function cancelSalesOrder(CancelSalesOrderCommand $command): SalesOrder
    {
        return $this->cancelSalesOrderHandler->handle($command);
    }

    /**
     * Delete a sales order.
     */
    public function deleteSalesOrder(DeleteSalesOrderCommand $command): void
    {
        $this->deleteSalesOrderHandler->handle($command);
    }
}
