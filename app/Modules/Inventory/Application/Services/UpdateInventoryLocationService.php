<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Core\Domain\ValueObjects\Metadata;
use Modules\Inventory\Application\Contracts\UpdateInventoryLocationServiceInterface;
use Modules\Inventory\Application\DTOs\UpdateInventoryLocationData;
use Modules\Inventory\Domain\Events\InventoryLocationUpdated;
use Modules\Inventory\Domain\Exceptions\InventoryLocationNotFoundException;
use Modules\Inventory\Domain\RepositoryInterfaces\InventoryLocationRepositoryInterface;

class UpdateInventoryLocationService extends BaseService implements UpdateInventoryLocationServiceInterface
{
    public function __construct(private readonly InventoryLocationRepositoryInterface $locationRepository)
    {
        parent::__construct($locationRepository);
    }

    protected function handle(array $data): mixed
    {
        $dto      = UpdateInventoryLocationData::fromArray($data);
        $location = $this->locationRepository->find($dto->id);

        if (! $location) {
            throw new InventoryLocationNotFoundException($dto->id);
        }

        $location->updateDetails(
            warehouseId: $location->getWarehouseId(),
            name:        $dto->name        ?? $location->getName(),
            type:        $dto->type        ?? $location->getType(),
            zoneId:      $dto->zoneId      ?? $location->getZoneId(),
            code:        $dto->code        ?? $location->getCode(),
            aisle:       $dto->aisle       ?? $location->getAisle(),
            row:         $dto->row         ?? $location->getRow(),
            level:       $dto->level       ?? $location->getLevel(),
            bin:         $dto->bin         ?? $location->getBin(),
            capacity:    $dto->capacity    ?? $location->getCapacity(),
            weightLimit: $dto->weightLimit ?? $location->getWeightLimit(),
            barcode:     $dto->barcode     ?? $location->getBarcode(),
            qrCode:      $dto->qrCode      ?? $location->getQrCode(),
            isPickable:  $dto->isPickable  ?? $location->isPickable(),
            isStorable:  $dto->isStorable  ?? $location->isStorable(),
            isPacking:   $dto->isPacking   ?? $location->isPacking(),
            isActive:    $dto->isActive    ?? $location->isActive(),
            metadata:    $dto->metadata !== null ? new Metadata($dto->metadata) : $location->getMetadata(),
        );

        $saved = $this->locationRepository->save($location);
        $this->addEvent(new InventoryLocationUpdated($saved));

        return $saved;
    }
}
