<?php

declare(strict_types=1);

namespace Modules\Dispatch\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Core\Domain\ValueObjects\Metadata;
use Modules\Dispatch\Application\Contracts\CreateDispatchServiceInterface;
use Modules\Dispatch\Application\DTOs\DispatchData;
use Modules\Dispatch\Domain\Entities\Dispatch;
use Modules\Dispatch\Domain\Events\DispatchCreated;
use Modules\Dispatch\Domain\RepositoryInterfaces\DispatchRepositoryInterface;

class CreateDispatchService extends BaseService implements CreateDispatchServiceInterface
{
    public function __construct(private readonly DispatchRepositoryInterface $dispatchRepository)
    {
        parent::__construct($dispatchRepository);
    }

    protected function handle(array $data): Dispatch
    {
        $dto = DispatchData::fromArray($data);

        $dispatch = new Dispatch(
            tenantId:              $dto->tenantId,
            referenceNumber:       $dto->referenceNumber,
            warehouseId:           $dto->warehouseId,
            customerId:            $dto->customerId,
            dispatchDate:          $dto->dispatchDate,
            salesOrderId:          $dto->salesOrderId,
            customerReference:     $dto->customerReference,
            estimatedDeliveryDate: $dto->estimatedDeliveryDate,
            carrier:               $dto->carrier,
            notes:                 $dto->notes,
            metadata:              $dto->metadata ? new Metadata($dto->metadata) : null,
            status:                $dto->status,
            currency:              $dto->currency,
            totalWeight:           $dto->totalWeight,
        );

        $saved = $this->dispatchRepository->save($dispatch);
        $this->addEvent(new DispatchCreated($saved->getId(), $saved->getTenantId()));

        return $saved;
    }
}
