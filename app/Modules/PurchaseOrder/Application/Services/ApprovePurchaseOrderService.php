<?php

declare(strict_types=1);

namespace Modules\PurchaseOrder\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\PurchaseOrder\Application\Contracts\ApprovePurchaseOrderServiceInterface;
use Modules\PurchaseOrder\Domain\Entities\PurchaseOrder;
use Modules\PurchaseOrder\Domain\Events\PurchaseOrderApproved;
use Modules\PurchaseOrder\Domain\Exceptions\PurchaseOrderNotFoundException;
use Modules\PurchaseOrder\Domain\RepositoryInterfaces\PurchaseOrderRepositoryInterface;

class ApprovePurchaseOrderService extends BaseService implements ApprovePurchaseOrderServiceInterface
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

        $order->approve((int) $data['approved_by']);

        $saved = $this->orderRepository->save($order);
        $this->addEvent(new PurchaseOrderApproved($saved->getId(), (int) $data['approved_by']));

        return $saved;
    }
}
