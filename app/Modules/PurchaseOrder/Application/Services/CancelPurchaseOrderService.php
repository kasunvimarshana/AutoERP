<?php

declare(strict_types=1);

namespace Modules\PurchaseOrder\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\PurchaseOrder\Application\Contracts\CancelPurchaseOrderServiceInterface;
use Modules\PurchaseOrder\Domain\Entities\PurchaseOrder;
use Modules\PurchaseOrder\Domain\Events\PurchaseOrderCancelled;
use Modules\PurchaseOrder\Domain\Exceptions\PurchaseOrderNotFoundException;
use Modules\PurchaseOrder\Domain\RepositoryInterfaces\PurchaseOrderRepositoryInterface;

class CancelPurchaseOrderService extends BaseService implements CancelPurchaseOrderServiceInterface
{
    public function __construct(private readonly PurchaseOrderRepositoryInterface $orderRepository)
    {
        parent::__construct($orderRepository);
    }

    protected function handle(array $data): PurchaseOrder
    {
        $id    = $data['id'];
        $order = $this->orderRepository->find($id);

        if (! $order) {
            throw new PurchaseOrderNotFoundException($id);
        }

        $order->cancel();

        $saved = $this->orderRepository->save($order);
        $this->addEvent(new PurchaseOrderCancelled($saved->getId()));

        return $saved;
    }
}
