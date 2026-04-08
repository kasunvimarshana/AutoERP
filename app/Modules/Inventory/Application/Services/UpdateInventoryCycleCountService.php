<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Core\Domain\ValueObjects\Metadata;
use Modules\Inventory\Application\Contracts\UpdateInventoryCycleCountServiceInterface;
use Modules\Inventory\Application\DTOs\UpdateInventoryCycleCountData;
use Modules\Inventory\Domain\Events\InventoryCycleCountCreated;
use Modules\Inventory\Domain\Exceptions\InventoryCycleCountNotFoundException;
use Modules\Inventory\Domain\RepositoryInterfaces\InventoryCycleCountRepositoryInterface;

class UpdateInventoryCycleCountService extends BaseService implements UpdateInventoryCycleCountServiceInterface
{
    public function __construct(private readonly InventoryCycleCountRepositoryInterface $cycleCountRepository)
    {
        parent::__construct($cycleCountRepository);
    }

    protected function handle(array $data): mixed
    {
        $dto   = UpdateInventoryCycleCountData::fromArray($data);
        $count = $this->cycleCountRepository->find($dto->id);

        if (! $count) {
            throw new InventoryCycleCountNotFoundException($dto->id);
        }

        $scheduledAt = $dto->scheduledAt ? new \DateTimeImmutable($dto->scheduledAt) : $count->getScheduledAt();

        $count->updateDetails(
            referenceNumber: $count->getReferenceNumber(),
            warehouseId:     $count->getWarehouseId(),
            zoneId:          $dto->zoneId      ?? $count->getZoneId(),
            locationId:      $dto->locationId  ?? $count->getLocationId(),
            countMethod:     $dto->countMethod ?? $count->getCountMethod(),
            assignedTo:      $dto->assignedTo  ?? $count->getAssignedTo(),
            scheduledAt:     $scheduledAt,
            notes:           $dto->notes       ?? $count->getNotes(),
            metadata:        $dto->metadata !== null ? new Metadata($dto->metadata) : $count->getMetadata(),
        );

        $saved = $this->cycleCountRepository->save($count);

        return $saved;
    }
}
