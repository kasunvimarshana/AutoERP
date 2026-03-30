<?php

declare(strict_types=1);

namespace Modules\Warehouse\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Core\Domain\ValueObjects\Code;
use Modules\Core\Domain\ValueObjects\Metadata;
use Modules\Core\Domain\ValueObjects\Name;
use Modules\Warehouse\Application\Contracts\UpdateWarehouseZoneServiceInterface;
use Modules\Warehouse\Application\DTOs\UpdateWarehouseZoneData;
use Modules\Warehouse\Domain\Entities\WarehouseZone;
use Modules\Warehouse\Domain\Events\WarehouseZoneUpdated;
use Modules\Warehouse\Domain\Exceptions\WarehouseZoneNotFoundException;
use Modules\Warehouse\Domain\RepositoryInterfaces\WarehouseZoneRepositoryInterface;

class UpdateWarehouseZoneService extends BaseService implements UpdateWarehouseZoneServiceInterface
{
    private WarehouseZoneRepositoryInterface $zoneRepository;

    public function __construct(WarehouseZoneRepositoryInterface $repository)
    {
        parent::__construct($repository);
        $this->zoneRepository = $repository;
    }

    protected function handle(array $data): WarehouseZone
    {
        $dto  = UpdateWarehouseZoneData::fromArray($data);
        $id   = (int) ($dto->id ?? 0);
        $zone = $this->zoneRepository->find($id);
        if (! $zone) {
            throw new WarehouseZoneNotFoundException($id);
        }

        // isProvided() distinguishes "field was absent" from "field was sent as null",
        // enabling safe partial updates that never unintentionally clear existing data.
        $name = $dto->isProvided('name')
            ? new Name((string) $dto->name)
            : $zone->getName();

        $type = $dto->isProvided('type')
            ? (string) $dto->type
            : $zone->getType();

        $code = $dto->isProvided('code')
            ? ($dto->code !== null ? new Code($dto->code) : null)
            : $zone->getCode();

        $description = $dto->isProvided('description')
            ? $dto->description
            : $zone->getDescription();

        $capacity = $dto->isProvided('capacity')
            ? $dto->capacity
            : $zone->getCapacity();

        $metadata = $dto->isProvided('metadata')
            ? ($dto->metadata !== null ? new Metadata($dto->metadata) : null)
            : $zone->getMetadata();

        $isActive = $dto->isProvided('is_active')
            ? (bool) $dto->is_active
            : $zone->isActive();

        $zone->updateDetails($name, $type, $code, $description, $capacity, $metadata, $isActive);

        // Only move the node when parent_zone_id was explicitly supplied and differs.
        if ($dto->isProvided('parent_zone_id') && $dto->parent_zone_id !== $zone->getParentZoneId()) {
            $this->zoneRepository->moveNode($id, $dto->parent_zone_id);
        }

        $saved = $this->zoneRepository->save($zone);
        $this->addEvent(new WarehouseZoneUpdated($saved));

        return $saved;
    }
}
