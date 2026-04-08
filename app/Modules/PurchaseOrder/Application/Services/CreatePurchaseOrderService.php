<?php

declare(strict_types=1);

namespace Modules\PurchaseOrder\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Core\Domain\ValueObjects\Metadata;
use Modules\PurchaseOrder\Application\Contracts\CreatePurchaseOrderServiceInterface;
use Modules\PurchaseOrder\Application\DTOs\PurchaseOrderData;
use Modules\PurchaseOrder\Domain\Entities\PurchaseOrder;
use Modules\PurchaseOrder\Domain\Events\PurchaseOrderCreated;
use Modules\PurchaseOrder\Domain\RepositoryInterfaces\PurchaseOrderRepositoryInterface;

class CreatePurchaseOrderService extends BaseService implements CreatePurchaseOrderServiceInterface
{
    public function __construct(private readonly PurchaseOrderRepositoryInterface $orderRepository)
    {
        parent::__construct($orderRepository);
    }

    protected function handle(array $data): PurchaseOrder
    {
        $dto = PurchaseOrderData::fromArray($data);

        $order = new PurchaseOrder(
            tenantId:          $dto->tenantId,
            referenceNumber:   $dto->referenceNumber,
            supplierId:        $dto->supplierId,
            orderDate:         $dto->orderDate,
            supplierReference: $dto->supplierReference,
            expectedDate:      $dto->expectedDate,
            warehouseId:       $dto->warehouseId,
            currency:          $dto->currency,
            subtotal:          $dto->subtotal,
            taxAmount:         $dto->taxAmount,
            discountAmount:    $dto->discountAmount,
            totalAmount:       $dto->totalAmount,
            notes:             $dto->notes,
            metadata:          $dto->metadata ? new Metadata($dto->metadata) : null,
            status:            $dto->status,
        );

        $saved = $this->orderRepository->save($order);
        $this->addEvent(new PurchaseOrderCreated($saved->getId(), $saved->getTenantId()));

        return $saved;
    }
}
