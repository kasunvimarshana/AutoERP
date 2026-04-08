<?php

declare(strict_types=1);

namespace Modules\Warehouse\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Core\Domain\ValueObjects\Code;
use Modules\Core\Domain\ValueObjects\Metadata;
use Modules\Core\Domain\ValueObjects\Name;
use Modules\Warehouse\Application\Contracts\CreateWarehouseServiceInterface;
use Modules\Warehouse\Application\DTOs\WarehouseData;
use Modules\Warehouse\Domain\Entities\Warehouse;
use Modules\Warehouse\Domain\Events\WarehouseCreated;
use Modules\Warehouse\Domain\RepositoryInterfaces\WarehouseRepositoryInterface;

class CreateWarehouseService extends BaseService implements CreateWarehouseServiceInterface
{
    private WarehouseRepositoryInterface $warehouseRepository;

    public function __construct(WarehouseRepositoryInterface $repository)
    {
        parent::__construct($repository);
        $this->warehouseRepository = $repository;
    }

    protected function handle(array $data): Warehouse
    {
        $dto = WarehouseData::fromArray($data);

        $name     = new Name($dto->name);
        $code     = $dto->code !== null ? new Code($dto->code) : null;
        $metadata = $dto->metadata ? new Metadata($dto->metadata) : null;

        $warehouse = new Warehouse(
            tenantId:    $dto->tenant_id,
            name:        $name,
            type:        $dto->type,
            code:        $code,
            description: $dto->description,
            address:     $dto->address,
            capacity:    $dto->capacity,
            locationId:  $dto->location_id,
            metadata:    $metadata,
            isActive:    $dto->is_active,
        );

        $saved = $this->warehouseRepository->save($warehouse);
        $this->addEvent(new WarehouseCreated($saved));

        return $saved;
    }
}
