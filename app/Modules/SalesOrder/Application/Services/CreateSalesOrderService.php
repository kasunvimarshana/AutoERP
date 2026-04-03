<?php

declare(strict_types=1);

namespace Modules\SalesOrder\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Core\Domain\ValueObjects\Metadata;
use Modules\SalesOrder\Application\Contracts\CreateSalesOrderServiceInterface;
use Modules\SalesOrder\Application\DTOs\SalesOrderData;
use Modules\SalesOrder\Domain\Entities\SalesOrder;
use Modules\SalesOrder\Domain\Events\SalesOrderCreated;
use Modules\SalesOrder\Domain\RepositoryInterfaces\SalesOrderRepositoryInterface;

class CreateSalesOrderService extends BaseService implements CreateSalesOrderServiceInterface
{
    public function __construct(private readonly SalesOrderRepositoryInterface $orderRepository)
    {
        parent::__construct($orderRepository);
    }

    protected function handle(array $data): SalesOrder
    {
        $dto = SalesOrderData::fromArray($data);

        $order = new SalesOrder(
            tenantId:          $dto->tenantId,
            referenceNumber:   $dto->referenceNumber,
            customerId:        $dto->customerId,
            orderDate:         $dto->orderDate,
            customerReference: $dto->customerReference,
            requiredDate:      $dto->requiredDate,
            warehouseId:       $dto->warehouseId,
            currency:          $dto->currency,
            subtotal:          $dto->subtotal,
            taxAmount:         $dto->taxAmount,
            discountAmount:    $dto->discountAmount,
            totalAmount:       $dto->totalAmount,
            shippingAddress:   $dto->shippingAddress,
            notes:             $dto->notes,
            metadata:          $dto->metadata ? new Metadata($dto->metadata) : null,
            status:            $dto->status,
        );

        $saved = $this->orderRepository->save($order);
        $this->addEvent(new SalesOrderCreated($saved->getId(), $saved->getTenantId()));

        return $saved;
    }
}
