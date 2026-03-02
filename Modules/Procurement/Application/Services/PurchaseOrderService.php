<?php

declare(strict_types=1);

namespace Modules\Procurement\Application\Services;

use Modules\Procurement\Application\Commands\CancelPurchaseOrderCommand;
use Modules\Procurement\Application\Commands\ConfirmPurchaseOrderCommand;
use Modules\Procurement\Application\Commands\CreatePurchaseOrderCommand;
use Modules\Procurement\Application\Commands\DeletePurchaseOrderCommand;
use Modules\Procurement\Application\Commands\ReceiveGoodsCommand;
use Modules\Procurement\Application\Handlers\CancelPurchaseOrderHandler;
use Modules\Procurement\Application\Handlers\ConfirmPurchaseOrderHandler;
use Modules\Procurement\Application\Handlers\CreatePurchaseOrderHandler;
use Modules\Procurement\Application\Handlers\DeletePurchaseOrderHandler;
use Modules\Procurement\Application\Handlers\ReceiveGoodsHandler;
use Modules\Procurement\Domain\Contracts\PurchaseOrderRepositoryInterface;
use Modules\Procurement\Domain\Entities\PurchaseOrder;

/**
 * Service orchestrating all purchase-order operations.
 *
 * Controllers must interact with the purchase-order domain exclusively through
 * this service. Read operations are fulfilled directly via the repository
 * contract; write operations are delegated to the appropriate command handlers.
 */
class PurchaseOrderService
{
    public function __construct(
        private readonly PurchaseOrderRepositoryInterface $purchaseOrderRepository,
        private readonly CreatePurchaseOrderHandler $createPurchaseOrderHandler,
        private readonly ConfirmPurchaseOrderHandler $confirmPurchaseOrderHandler,
        private readonly ReceiveGoodsHandler $receiveGoodsHandler,
        private readonly CancelPurchaseOrderHandler $cancelPurchaseOrderHandler,
        private readonly DeletePurchaseOrderHandler $deletePurchaseOrderHandler,
    ) {}

    /**
     * Retrieve a paginated list of purchase orders for the given tenant.
     *
     * @return array{items: PurchaseOrder[], current_page: int, last_page: int, per_page: int, total: int}
     */
    public function listPurchaseOrders(int $tenantId, int $page, int $perPage): array
    {
        return $this->purchaseOrderRepository->findAll($tenantId, $page, $perPage);
    }

    /**
     * Find a single purchase order by its identifier within the given tenant.
     */
    public function findPurchaseOrderById(int $orderId, int $tenantId): ?PurchaseOrder
    {
        return $this->purchaseOrderRepository->findById($orderId, $tenantId);
    }

    /**
     * Create a new purchase order and return the persisted entity.
     */
    public function createPurchaseOrder(CreatePurchaseOrderCommand $command): PurchaseOrder
    {
        return $this->createPurchaseOrderHandler->handle($command);
    }

    /**
     * Confirm a draft purchase order and return the updated entity.
     */
    public function confirmPurchaseOrder(ConfirmPurchaseOrderCommand $command): PurchaseOrder
    {
        return $this->confirmPurchaseOrderHandler->handle($command);
    }

    /**
     * Receive goods against a purchase order and return the updated entity.
     */
    public function receiveGoods(ReceiveGoodsCommand $command): PurchaseOrder
    {
        return $this->receiveGoodsHandler->handle($command);
    }

    /**
     * Cancel a purchase order and return the updated entity.
     */
    public function cancelPurchaseOrder(CancelPurchaseOrderCommand $command): PurchaseOrder
    {
        return $this->cancelPurchaseOrderHandler->handle($command);
    }

    /**
     * Delete a purchase order.
     */
    public function deletePurchaseOrder(DeletePurchaseOrderCommand $command): void
    {
        $this->deletePurchaseOrderHandler->handle($command);
    }
}
