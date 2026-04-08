<?php

declare(strict_types=1);

namespace Modules\Dispatch\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Dispatch\Application\Contracts\UpdateDispatchServiceInterface;
use Modules\Dispatch\Application\DTOs\UpdateDispatchData;
use Modules\Dispatch\Domain\Entities\Dispatch;
use Modules\Dispatch\Domain\Events\DispatchUpdated;
use Modules\Dispatch\Domain\Exceptions\DispatchNotFoundException;
use Modules\Dispatch\Domain\RepositoryInterfaces\DispatchRepositoryInterface;

class UpdateDispatchService extends BaseService implements UpdateDispatchServiceInterface
{
    public function __construct(private readonly DispatchRepositoryInterface $dispatchRepository)
    {
        parent::__construct($dispatchRepository);
    }

    protected function handle(array $data): Dispatch
    {
        $dto      = UpdateDispatchData::fromArray($data);
        $dispatch = $this->dispatchRepository->find($dto->id);

        if (! $dispatch) {
            throw new DispatchNotFoundException($dto->id);
        }

        $dispatch->updateDetails(
            $dto->customerReference,
            $dto->estimatedDeliveryDate,
            $dto->carrier,
            $dto->trackingNumber,
            $dto->notes,
            $dto->metadata,
            $dto->totalWeight,
        );

        $saved = $this->dispatchRepository->save($dispatch);
        $this->addEvent(new DispatchUpdated($saved->getId(), $saved->getTenantId()));

        return $saved;
    }
}
