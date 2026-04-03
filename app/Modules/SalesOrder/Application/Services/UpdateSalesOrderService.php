<?php

declare(strict_types=1);

namespace Modules\SalesOrder\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\SalesOrder\Application\Contracts\UpdateSalesOrderServiceInterface;
use Modules\SalesOrder\Application\DTOs\UpdateSalesOrderData;
use Modules\SalesOrder\Domain\Entities\SalesOrder;
use Modules\SalesOrder\Domain\Events\SalesOrderUpdated;
use Modules\SalesOrder\Domain\Exceptions\SalesOrderNotFoundException;
use Modules\SalesOrder\Domain\RepositoryInterfaces\SalesOrderRepositoryInterface;

class UpdateSalesOrderService extends BaseService implements UpdateSalesOrderServiceInterface
{
    public function __construct(private readonly SalesOrderRepositoryInterface $orderRepository)
    {
        parent::__construct($orderRepository);
    }

    protected function handle(array $data): SalesOrder
    {
        $dto   = UpdateSalesOrderData::fromArray($data);
        $order = $this->orderRepository->find($dto->id);

        if (! $order) {
            throw new SalesOrderNotFoundException($dto->id);
        }

        $order->updateDetails(
            $dto->customerReference,
            $dto->requiredDate,
            $dto->warehouseId,
            $dto->shippingAddress,
            $dto->notes,
            $dto->metadata,
        );

        $saved = $this->orderRepository->save($order);
        $this->addEvent(new SalesOrderUpdated($saved->getId(), $saved->getTenantId()));

        return $saved;
    }
}
