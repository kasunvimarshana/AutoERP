<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Core\Domain\ValueObjects\Metadata;
use Modules\Inventory\Application\Contracts\CreateInventoryLocationServiceInterface;
use Modules\Inventory\Application\DTOs\InventoryLocationData;
use Modules\Inventory\Domain\Entities\InventoryLocation;
use Modules\Inventory\Domain\Events\InventoryLocationCreated;
use Modules\Inventory\Domain\RepositoryInterfaces\InventoryLocationRepositoryInterface;

class CreateInventoryLocationService extends BaseService implements CreateInventoryLocationServiceInterface
{
    public function __construct(private readonly InventoryLocationRepositoryInterface $locationRepository)
    {
        parent::__construct($locationRepository);
    }

    protected function handle(array $data): InventoryLocation
    {
        $dto = InventoryLocationData::fromArray($data);

        $location = new InventoryLocation(
            tenantId:    $dto->tenantId,
            warehouseId: $dto->warehouseId,
            name:        $dto->name,
            type:        $dto->type,
            zoneId:      $dto->zoneId,
            code:        $dto->code,
            aisle:       $dto->aisle,
            row:         $dto->row,
            level:       $dto->level,
            bin:         $dto->bin,
            capacity:    $dto->capacity,
            weightLimit: $dto->weightLimit,
            barcode:     $dto->barcode,
            qrCode:      $dto->qrCode,
            isPickable:  $dto->isPickable,
            isStorable:  $dto->isStorable,
            isPacking:   $dto->isPacking,
            isActive:    $dto->isActive,
            metadata:    $dto->metadata ? new Metadata($dto->metadata) : null,
        );

        $saved = $this->locationRepository->save($location);
        $this->addEvent(new InventoryLocationCreated($saved));

        return $saved;
    }
}
