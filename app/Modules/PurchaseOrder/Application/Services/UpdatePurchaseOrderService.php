<?php

declare(strict_types=1);

namespace Modules\PurchaseOrder\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\PurchaseOrder\Application\Contracts\UpdatePurchaseOrderServiceInterface;
use Modules\PurchaseOrder\Application\DTOs\UpdatePurchaseOrderData;
use Modules\PurchaseOrder\Domain\Entities\PurchaseOrder;
use Modules\PurchaseOrder\Domain\Events\PurchaseOrderUpdated;
use Modules\PurchaseOrder\Domain\Exceptions\PurchaseOrderNotFoundException;
use Modules\PurchaseOrder\Domain\RepositoryInterfaces\PurchaseOrderRepositoryInterface;

class UpdatePurchaseOrderService extends BaseService implements UpdatePurchaseOrderServiceInterface
{
    public function __construct(private readonly PurchaseOrderRepositoryInterface $orderRepository)
    {
        parent::__construct($orderRepository);
    }

    protected function handle(array $data): PurchaseOrder
    {
        $dto   = UpdatePurchaseOrderData::fromArray($data);
        $order = $this->orderRepository->find($dto->id);

        if (! $order) {
            throw new PurchaseOrderNotFoundException($dto->id);
        }

        $order->updateDetails(
            $dto->supplierReference,
            $dto->expectedDate,
            $dto->warehouseId,
            $dto->notes,
            $dto->metadata,
        );

        $saved = $this->orderRepository->save($order);
        $this->addEvent(new PurchaseOrderUpdated($saved->getId(), $saved->getTenantId()));

        return $saved;
    }
}
