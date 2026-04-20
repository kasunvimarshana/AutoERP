<?php

declare(strict_types=1);

namespace Modules\Warehouse\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Warehouse\Application\Contracts\CreateWarehouseServiceInterface;
use Modules\Warehouse\Application\DTOs\CreateWarehouseDTO;
use Modules\Warehouse\Domain\Entities\Warehouse;
use Modules\Warehouse\Domain\RepositoryInterfaces\WarehouseRepositoryInterface;

class CreateWarehouseService extends BaseService implements CreateWarehouseServiceInterface
{
    public function __construct(private readonly WarehouseRepositoryInterface $warehouseRepository)
    {
        parent::__construct($warehouseRepository);
    }

    protected function handle(array $data): Warehouse
    {
        $dto = new CreateWarehouseDTO(
            tenantId: (int) $data['tenant_id'],
            orgUnitId: isset($data['org_unit_id']) ? (int) $data['org_unit_id'] : null,
            name: (string) $data['name'],
            code: $data['code'] ?? null,
            imagePath: $data['image_path'] ?? null,
            type: (string) ($data['type'] ?? 'standard'),
            addressId: isset($data['address_id']) ? (int) $data['address_id'] : null,
            isActive: (bool) ($data['is_active'] ?? true),
            isDefault: (bool) ($data['is_default'] ?? false),
            metadata: is_array($data['metadata'] ?? null) ? $data['metadata'] : null,
        );

        if ($dto->isDefault) {
            $this->warehouseRepository->clearDefaultForTenant($dto->tenantId);
        }

        return $this->warehouseRepository->save(new Warehouse(
            tenantId: $dto->tenantId,
            orgUnitId: $dto->orgUnitId,
            name: $dto->name,
            code: $dto->code,
            imagePath: $dto->imagePath,
            type: $dto->type,
            addressId: $dto->addressId,
            isActive: $dto->isActive,
            isDefault: $dto->isDefault,
            metadata: $dto->metadata,
        ));
    }
}
