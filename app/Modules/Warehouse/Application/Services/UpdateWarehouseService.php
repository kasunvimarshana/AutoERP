<?php

declare(strict_types=1);

namespace Modules\Warehouse\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Core\Domain\Exceptions\ConcurrentModificationException;
use Modules\Core\Domain\Exceptions\NotFoundException;
use Modules\Warehouse\Application\Contracts\UpdateWarehouseServiceInterface;
use Modules\Warehouse\Application\DTOs\UpdateWarehouseData;
use Modules\Warehouse\Domain\Entities\Warehouse;
use Modules\Warehouse\Domain\RepositoryInterfaces\WarehouseRepositoryInterface;

class UpdateWarehouseService extends BaseService implements UpdateWarehouseServiceInterface
{
    public function __construct(private readonly WarehouseRepositoryInterface $warehouseRepository)
    {
        parent::__construct($warehouseRepository);
    }

    protected function handle(array $data): Warehouse
    {
        $dto = new UpdateWarehouseData(
            id: (int) $data['id'],
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
            rowVersion: (int) ($data['row_version'] ?? 1),
        );

        $warehouse = $this->warehouseRepository->find($dto->id);

        if (! $warehouse instanceof Warehouse || $warehouse->getTenantId() !== $dto->tenantId) {
            throw new NotFoundException('Warehouse', $dto->id);
        }

        if ($dto->rowVersion !== $warehouse->getRowVersion()) {
            throw new ConcurrentModificationException('Warehouse', $dto->id);
        }

        if ($dto->isDefault) {
            $this->warehouseRepository->clearDefaultForTenant($dto->tenantId, $dto->id);
        }

        $warehouse->update(
            name: $dto->name,
            type: $dto->type,
            orgUnitId: $dto->orgUnitId,
            code: $dto->code,
            imagePath: $dto->imagePath,
            addressId: $dto->addressId,
            isActive: $dto->isActive,
            isDefault: $dto->isDefault,
            metadata: $dto->metadata,
        );

        return $this->warehouseRepository->save($warehouse);
    }
}
