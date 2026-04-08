<?php

declare(strict_types=1);

namespace Modules\Warehouse\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Core\Domain\ValueObjects\Code;
use Modules\Core\Domain\ValueObjects\Metadata;
use Modules\Core\Domain\ValueObjects\Name;
use Modules\Warehouse\Application\Contracts\CreateWarehouseZoneServiceInterface;
use Modules\Warehouse\Application\DTOs\WarehouseZoneData;
use Modules\Warehouse\Domain\Entities\WarehouseZone;
use Modules\Warehouse\Domain\Events\WarehouseZoneCreated;
use Modules\Warehouse\Domain\RepositoryInterfaces\WarehouseZoneRepositoryInterface;

class CreateWarehouseZoneService extends BaseService implements CreateWarehouseZoneServiceInterface
{
    private WarehouseZoneRepositoryInterface $zoneRepository;

    public function __construct(WarehouseZoneRepositoryInterface $repository)
    {
        parent::__construct($repository);
        $this->zoneRepository = $repository;
    }

    protected function handle(array $data): WarehouseZone
    {
        $dto = WarehouseZoneData::fromArray($data);

        $name     = new Name($dto->name);
        $code     = $dto->code !== null ? new Code($dto->code) : null;
        $metadata = $dto->metadata ? new Metadata($dto->metadata) : null;

        $zone = new WarehouseZone(
            warehouseId:  $dto->warehouse_id,
            tenantId:     $dto->tenant_id,
            name:         $name,
            type:         $dto->type,
            code:         $code,
            description:  $dto->description,
            capacity:     $dto->capacity,
            metadata:     $metadata,
            isActive:     $dto->is_active,
            parentZoneId: $dto->parent_zone_id,
        );

        $saved = $this->zoneRepository->save($zone);
        $this->addEvent(new WarehouseZoneCreated($saved));

        return $saved;
    }
}
