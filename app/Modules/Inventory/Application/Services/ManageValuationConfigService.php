<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Services;

use Illuminate\Support\Facades\DB;
use Modules\Core\Domain\Exceptions\NotFoundException;
use Modules\Inventory\Application\Contracts\ManageValuationConfigServiceInterface;
use Modules\Inventory\Application\DTOs\ValuationConfigData;
use Modules\Inventory\Domain\Entities\ValuationConfig;
use Modules\Inventory\Domain\RepositoryInterfaces\ValuationConfigRepositoryInterface;

class ManageValuationConfigService implements ManageValuationConfigServiceInterface
{
    public function __construct(
        private readonly ValuationConfigRepositoryInterface $valuationConfigRepository,
    ) {}

    public function create(array $data): ValuationConfig
    {
        $dto = new ValuationConfigData(
            tenantId: (int) $data['tenant_id'],
            orgUnitId: isset($data['org_unit_id']) ? (int) $data['org_unit_id'] : null,
            warehouseId: isset($data['warehouse_id']) ? (int) $data['warehouse_id'] : null,
            productId: isset($data['product_id']) ? (int) $data['product_id'] : null,
            transactionType: $data['transaction_type'] ?? null,
            valuationMethod: (string) $data['valuation_method'],
            allocationStrategy: (string) $data['allocation_strategy'],
            isActive: (bool) ($data['is_active'] ?? true),
            metadata: is_array($data['metadata'] ?? null) ? $data['metadata'] : null,
        );

        $config = new ValuationConfig(
            tenantId: $dto->tenantId,
            orgUnitId: $dto->orgUnitId,
            warehouseId: $dto->warehouseId,
            productId: $dto->productId,
            transactionType: $dto->transactionType,
            valuationMethod: $dto->valuationMethod,
            allocationStrategy: $dto->allocationStrategy,
            isActive: $dto->isActive,
            metadata: $dto->metadata,
        );

        return DB::transaction(fn () => $this->valuationConfigRepository->create($config));
    }

    public function update(int $tenantId, int $id, array $data): ValuationConfig
    {
        $existing = $this->valuationConfigRepository->findById($tenantId, $id);

        if ($existing === null) {
            throw new NotFoundException('ValuationConfig', $id);
        }

        $updated = new ValuationConfig(
            tenantId: $tenantId,
            orgUnitId: array_key_exists('org_unit_id', $data) ? (isset($data['org_unit_id']) ? (int) $data['org_unit_id'] : null) : $existing->getOrgUnitId(),
            warehouseId: array_key_exists('warehouse_id', $data) ? (isset($data['warehouse_id']) ? (int) $data['warehouse_id'] : null) : $existing->getWarehouseId(),
            productId: array_key_exists('product_id', $data) ? (isset($data['product_id']) ? (int) $data['product_id'] : null) : $existing->getProductId(),
            transactionType: array_key_exists('transaction_type', $data) ? $data['transaction_type'] : $existing->getTransactionType(),
            valuationMethod: $data['valuation_method'] ?? $existing->getValuationMethod(),
            allocationStrategy: $data['allocation_strategy'] ?? $existing->getAllocationStrategy(),
            isActive: array_key_exists('is_active', $data) ? (bool) $data['is_active'] : $existing->isActive(),
            metadata: array_key_exists('metadata', $data) ? (is_array($data['metadata']) ? $data['metadata'] : null) : $existing->getMetadata(),
            id: $id,
        );

        return DB::transaction(fn () => $this->valuationConfigRepository->update($updated));
    }

    public function delete(int $tenantId, int $id): void
    {
        $existing = $this->valuationConfigRepository->findById($tenantId, $id);

        if ($existing === null) {
            throw new NotFoundException('ValuationConfig', $id);
        }

        DB::transaction(fn () => $this->valuationConfigRepository->delete($tenantId, $id));
    }

    public function find(int $tenantId, int $id): ValuationConfig
    {
        $config = $this->valuationConfigRepository->findById($tenantId, $id);

        if ($config === null) {
            throw new NotFoundException('ValuationConfig', $id);
        }

        return $config;
    }

    public function list(int $tenantId, int $perPage = 15, int $page = 1): mixed
    {
        return $this->valuationConfigRepository->paginate($tenantId, $perPage, $page);
    }
}
