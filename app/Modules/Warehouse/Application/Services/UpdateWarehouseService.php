<?php

declare(strict_types=1);

namespace Modules\Warehouse\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Core\Domain\ValueObjects\Code;
use Modules\Core\Domain\ValueObjects\Metadata;
use Modules\Core\Domain\ValueObjects\Name;
use Modules\Warehouse\Application\Contracts\UpdateWarehouseServiceInterface;
use Modules\Warehouse\Application\DTOs\UpdateWarehouseData;
use Modules\Warehouse\Domain\Entities\Warehouse;
use Modules\Warehouse\Domain\Events\WarehouseUpdated;
use Modules\Warehouse\Domain\Exceptions\WarehouseNotFoundException;
use Modules\Warehouse\Domain\RepositoryInterfaces\WarehouseRepositoryInterface;

class UpdateWarehouseService extends BaseService implements UpdateWarehouseServiceInterface
{
    private WarehouseRepositoryInterface $warehouseRepository;

    public function __construct(WarehouseRepositoryInterface $repository)
    {
        parent::__construct($repository);
        $this->warehouseRepository = $repository;
    }

    protected function handle(array $data): Warehouse
    {
        $dto       = UpdateWarehouseData::fromArray($data);
        $id        = (int) ($dto->id ?? 0);
        $warehouse = $this->warehouseRepository->find($id);
        if (! $warehouse) {
            throw new WarehouseNotFoundException($id);
        }

        // isProvided() distinguishes "field was absent" from "field was sent as null",
        // enabling safe partial updates that never unintentionally clear existing data.
        $name = $dto->isProvided('name')
            ? new Name((string) $dto->name)
            : $warehouse->getName();

        $type = $dto->isProvided('type')
            ? (string) $dto->type
            : $warehouse->getType();

        $code = $dto->isProvided('code')
            ? ($dto->code !== null ? new Code($dto->code) : null)
            : $warehouse->getCode();

        $description = $dto->isProvided('description')
            ? $dto->description
            : $warehouse->getDescription();

        $address = $dto->isProvided('address')
            ? $dto->address
            : $warehouse->getAddress();

        $capacity = $dto->isProvided('capacity')
            ? $dto->capacity
            : $warehouse->getCapacity();

        $locationId = $dto->isProvided('location_id')
            ? $dto->location_id
            : $warehouse->getLocationId();

        $metadata = $dto->isProvided('metadata')
            ? ($dto->metadata !== null ? new Metadata($dto->metadata) : null)
            : $warehouse->getMetadata();

        $isActive = $dto->isProvided('is_active')
            ? (bool) $dto->is_active
            : $warehouse->isActive();

        $warehouse->updateDetails($name, $type, $code, $description, $address, $capacity, $locationId, $metadata, $isActive);

        $saved = $this->warehouseRepository->save($warehouse);
        $this->addEvent(new WarehouseUpdated($saved));

        return $saved;
    }
}
