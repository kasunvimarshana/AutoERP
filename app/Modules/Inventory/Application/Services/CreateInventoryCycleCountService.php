<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Core\Domain\ValueObjects\Metadata;
use Modules\Inventory\Application\Contracts\CreateInventoryCycleCountServiceInterface;
use Modules\Inventory\Application\DTOs\InventoryCycleCountData;
use Modules\Inventory\Domain\Entities\InventoryCycleCount;
use Modules\Inventory\Domain\Events\InventoryCycleCountCreated;
use Modules\Inventory\Domain\RepositoryInterfaces\InventoryCycleCountRepositoryInterface;

class CreateInventoryCycleCountService extends BaseService implements CreateInventoryCycleCountServiceInterface
{
    public function __construct(private readonly InventoryCycleCountRepositoryInterface $cycleCountRepository)
    {
        parent::__construct($cycleCountRepository);
    }

    protected function handle(array $data): InventoryCycleCount
    {
        $dto         = InventoryCycleCountData::fromArray($data);
        $scheduledAt = $dto->scheduledAt ? new \DateTimeImmutable($dto->scheduledAt) : null;

        $count = new InventoryCycleCount(
            tenantId:        $dto->tenantId,
            referenceNumber: $dto->referenceNumber,
            warehouseId:     $dto->warehouseId,
            zoneId:          $dto->zoneId,
            locationId:      $dto->locationId,
            countMethod:     $dto->countMethod,
            status:          $dto->status,
            assignedTo:      $dto->assignedTo,
            scheduledAt:     $scheduledAt,
            notes:           $dto->notes,
            metadata:        $dto->metadata ? new Metadata($dto->metadata) : null,
        );

        $saved = $this->cycleCountRepository->save($count);
        $this->addEvent(new InventoryCycleCountCreated($saved));

        return $saved;
    }
}
